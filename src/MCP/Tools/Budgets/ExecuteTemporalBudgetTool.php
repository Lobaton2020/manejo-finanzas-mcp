<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ExecuteTemporalBudgetTool extends BaseTool
{
    #[McpTool(
        name: 'execute_temporal_budget',
        description: 'Ejecuta TODOS los items activos de un presupuesto temporal: por cada item (status=1) crea un egreso real en outflows (validando disponibilidad del deposito). Si algun item falla, hace rollback completo. Parametros: idTemporalBudget (requerido), idUser (default 1), setDate (opcional, default hoy).'
    )]
    public function executeTemporalBudget(int $idTemporalBudget, int $idUser = 1, ?string $setDate = null): array
    {
        return $this->executeWithLogging(function () use ($idTemporalBudget, $idUser, $setDate) {
            $this->debug('executeTemporalBudget start', compact('idTemporalBudget', 'idUser', 'setDate'));

            $tb = $this->table('temporal_budgets')->where('id_temporal_budget', $idTemporalBudget)->first();
            if (!$tb || (int) $tb->id_user !== $idUser) {
                return $this->validationError('El presupuesto temporal no existe o no pertenece al usuario.');
            }

            $items = $this->table('temporal_budgets_outflow')
                ->where('id_temporal_budget', $idTemporalBudget)
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->get();

            if ($items->isEmpty()) {
                $this->debug('executeTemporalBudget no active items');
                return $this->successResponse([
                    'id_temporal_budget' => $idTemporalBudget,
                    'executed_count'     => 0,
                    'created_outflows'   => [],
                ], 'No hay items activos para ejecutar.');
            }

            $setDate = $setDate ?? date('Y-m-d');
            $created = [];

            $this->transaction(function () use ($items, $idUser, $setDate, &$created) {
                foreach ($items as $item) {
                    $deposit = $this->table('porcents')->where('id_porcent', $item->id_porcent)->where('id_user', $idUser)->where('status', 1)->first();
                    if (!$deposit) {
                        throw new \RuntimeException('Deposito invalido para el item ' . $item->id_temporal_budget_outflow);
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
                        throw new \RuntimeException("Saldo insuficiente en deposito {$item->id_porcent} para item {$item->id_temporal_budget_outflow}: disponible {$available}, requerido {$item->amount}");
                    }

                    $now = date('Y-m-d H:i:s');
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

                    $created[] = [
                        'id_temporal_budget_outflow' => (int) $item->id_temporal_budget_outflow,
                        'id_outflow'                 => (int) $outflowId,
                        'amount'                     => (float) $item->amount,
                    ];

                    $this->table('temporal_budgets_outflow')
                        ->where('id_temporal_budget_outflow', $item->id_temporal_budget_outflow)
                        ->update(['status' => 0, 'update_at' => $now]);
                }
            });

            $this->debug('executeTemporalBudget completed', ['executed' => count($created)]);

            return $this->successResponse([
                'id_temporal_budget' => $idTemporalBudget,
                'executed_count'     => count($created),
                'created_outflows'   => $created,
            ], 'Presupuesto ejecutado.');
        }, 'execute_temporal_budget', compact('idTemporalBudget', 'idUser', 'setDate'));
    }
}