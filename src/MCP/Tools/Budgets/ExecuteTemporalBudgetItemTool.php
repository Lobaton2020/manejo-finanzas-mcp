<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ExecuteTemporalBudgetItemTool extends BaseTool
{
    #[McpTool(
        name: 'execute_temporal_budget_item',
        description: 'Ejecuta UN item del presupuesto temporal (status=1) creando su outflow real. Valida disponibilidad. Parametros: idTemporalBudgetOutflow (requerido), idUser (default 1), setDate (opcional).'
    )]
    public function executeTemporalBudgetItem(int $idTemporalBudgetOutflow, int $idUser = 1, ?string $setDate = null): array
    {
        return $this->executeWithLogging(function () use ($idTemporalBudgetOutflow, $idUser, $setDate) {
            $this->debug('executeTemporalBudgetItem start', compact('idTemporalBudgetOutflow', 'idUser', 'setDate'));

            $item = $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->first();
            if (!$item || (int) $item->id_user !== $idUser) {
                return $this->validationError('El item no existe o no pertenece al usuario.');
            }
            if ((int) $item->status !== 1) {
                return $this->validationError('El item no esta activo. Solo se ejecutan items activos.');
            }

            $setDate = $setDate ?? date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            $result = $this->transaction(function () use ($item, $idUser, $setDate, $now) {
                $deposit = $this->table('porcents')->where('id_porcent', $item->id_porcent)->where('id_user', $idUser)->where('status', 1)->first();
                if (!$deposit) {
                    throw new \RuntimeException('Deposito invalido para el item.');
                }

                $balanceData = $this->table('porcents')
                    ->selectRaw('
                        (SELECT COALESCE(SUM(i.total * (ip.porcent / 100)), 0) FROM inflow_porcent ip
                         INNER JOIN inflows i ON i.id_inflow = ip.id_inflow
                         WHERE ip.id_porcent = porcents.id_porcent AND i.id_user = ? AND i.status = 1) as total_income,
                        (SELECT COALESCE(SUM(o.amount), 0) FROM outflows o
                         WHERE o.id_porcent = porcents.id_porcent AND o.id_user = ? AND o.status = 1) as total_outflow
                    ', [$idUser, $idUser])
                    ->where('id_porcent', $item->id_porcent)->first();

                $available = (float) ($balanceData->total_income ?? 0) - (float) ($balanceData->total_outflow ?? 0);
                if ((float) $item->amount > $available) {
                    throw new \RuntimeException("Saldo insuficiente: disponible {$available}, requerido {$item->amount}");
                }

                $outflowId = $this->table('outflows')->insertGetId([
                    'id_outflow_type' => $item->id_outflow_type,
                    'id_user'         => $idUser,
                    'id_category'     => $item->id_category,
                    'id_porcent'      => $item->id_porcent,
                    'amount'          => $item->amount,
                    'description'     => $item->description,
                    'set_date'        => $setDate,
                    'status'          => 1,
                    'update_at'       => $now,
                    'create_at'       => $now,
                    'is_in_budget'    => $item->is_in_budget,
                ]);

                $this->table('temporal_budgets_outflow')
                    ->where('id_temporal_budget_outflow', $item->id_temporal_budget_outflow)
                    ->update(['status' => 0, 'update_at' => $now]);

                return $outflowId;
            });

            $this->debug('executeTemporalBudgetItem completed', ['id_outflow' => $result]);

            return $this->successResponse([
                'id_temporal_budget_outflow' => $idTemporalBudgetOutflow,
                'id_outflow'                 => (int) $result,
                'amount'                     => (float) $item->amount,
                'set_date'                   => $setDate,
            ], 'Item ejecutado.');
        }, 'execute_temporal_budget_item', compact('idTemporalBudgetOutflow', 'idUser', 'setDate'));
    }
}