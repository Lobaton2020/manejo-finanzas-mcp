<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetAvailableByDepositsTool extends BaseTool
{
    /**
     * Obtiene los depósitos/cuentas con su balance disponible.
     * 
     * ¿Para qué sirve?: Saber cuánto dinero tienes disponible en cada cuenta/depósito.
     * Es CRÍTICO para validar que tienes suficiente dinero antes de hacer un egreso.
     * 
     * Lógica:
     * 1. Para cada depósito (cuenta), calcula:
     *    - total_income: suma de todos los ingresos distribuidos a ese depósito
     *    - total_outflow: suma de todos los egresos de ese depósito
     *    - available_balance: total_income - total_outflow
     * 2. Solo retorna depósitos con status = 1
     * 
     * Importante: El balance se calcula en tiempo real desde la BD.
     * 
     * Ejemplo de uso:
     *   1. Antes de outflow_money, llama esta función
     *   2. Verifica que el monto del egreso <= available_balance del idPorcent chosen
     *   3. Usa el id_porcent retornado para outflow_money
     * 
     * @param int $idUser ID del usuario (default: 1)
     * @return array Lista de depósitos con: id_porcent, name, total_income, total_outflow, available_balance
     */
    #[McpTool(
        name: 'get_available_by_deposits',
        description: 'Obtiene todos los depósitos/cuentas activos con su balance financiero. Para cada depósito calcula: total_income (ingresos), total_outflow (egresos), available_balance (balance disponible). Útil para saber cuánto dinero hay disponible en cada cuenta. Retorna: id_porcent, name, total_income, total_outflow, available_balance.'
    )]
    public function getAvailableByDeposits(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $deposits = $this->table('porcents')
                ->select([
                    'porcents.id_porcent',
                    'porcents.name',
                    'porcents.status',
                    'porcents.create_at',
                ])
                ->selectSub(
                    $this->table('inflow_porcent')
                        ->join('inflows', 'inflow_porcent.id_inflow', '=', 'inflows.id_inflow')
                        ->whereColumn('inflow_porcent.id_porcent', 'porcents.id_porcent')
                        ->where('inflows.id_user', $idUser)
                        ->where('inflows.status', 1)
                        ->selectRaw('COALESCE(SUM(inflows.total * (inflow_porcent.porcent / 100)), 0)'),
                    'total_income'
                )
                ->selectSub(
                    $this->table('outflows')
                        ->whereColumn('outflows.id_porcent', 'porcents.id_porcent')
                        ->where('outflows.id_user', $idUser)
                        ->where('outflows.status', 1)
                        ->selectRaw('COALESCE(SUM(outflows.amount), 0)'),
                    'total_outflow'
                )
                ->where('porcents.status', 1)
                ->where('porcents.id_user', $idUser)
                ->orderBy('porcents.name')
                ->get();

            if ($deposits->isEmpty()) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'No hay depósitos activos disponibles.'
                    ]
                ];
            }

            $formatted = $deposits->map(function ($deposit) {
                $totalIncome = round((float) $deposit->total_income, 2);
                $totalOutflow = round((float) $deposit->total_outflow, 2);
                $availableBalance = round($totalIncome - $totalOutflow, 2);

                return [
                    'id_porcent' => $deposit->id_porcent,
                    'name' => $deposit->name,
                    'status' => $deposit->status,
                    'create_at' => $deposit->create_at,
                    'total_income' => $totalIncome,
                    'total_outflow' => $totalOutflow,
                    'available_balance' => $availableBalance,
                ];
            })->toArray();

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($formatted, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_available_by_deposits', ['idUser' => $idUser]);
    }
}
