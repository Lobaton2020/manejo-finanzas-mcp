<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetAvailableByDepositsTool extends BaseTool
{
    /**
     * Get all active deposits (porcents) with their financial summary.
     * Calculates for each deposit:
     *   - total_income = SUM(inflow.total * inflow_porcent.porcent / 100)
     *   - total_outflow = SUM(outflow.amount)
     *   - available_balance = total_income - total_outflow
     * Requires idUser parameter (default: 1).
     * Only returns deposits with status = 1 (active).
     * Uses a single optimized query with subqueries.
     */
    #[McpTool(name: 'get_available_by_deposits')]
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
