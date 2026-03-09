<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetDepositsHistoryTool extends BaseTool
{
    #[McpTool(name: 'get_deposits_history', description: 'Obtiene el historial de depósitos por mes en series de tiempo. Para cada depósito, agrupa los ingresos y egresos por mes (formato YYYY-MM), calcula el total de ingresos, el total de egresos, y el saldo (ingreso - egreso) acumulado mes a mes. Útil para análisis financiero y seguimiento de cashflow por período.')]
    public function getDepositsHistory(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $deposits = $this->table('porcents')
                ->select([
                    'porcents.id_porcent',
                    'porcents.name',
                ])
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

            $formatted = $deposits->map(function ($deposit) use ($idUser) {
                $monthlyIncome = $this->table('inflow_porcent')
                    ->join('inflows', 'inflow_porcent.id_inflow', '=', 'inflows.id_inflow')
                    ->where('inflow_porcent.id_porcent', '=', 'inflow_porcent.id_porcent')
                    ->where('inflows.id_user', $idUser)
                    ->where('inflows.status', 1)
                    ->selectRaw('DATE_FORMAT(inflows.set_date, "%Y-%m") as month')
                    ->selectRaw('COALESCE(SUM(inflows.total * (inflow_porcent.porcent / 100)), 0) as income')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');

                $monthlyOutflow = $this->table('outflows')
                    ->where('outflows.id_porcent', '=', 'outflows.id_porcent')
                    ->where('outflows.id_user', $idUser)
                    ->where('outflows.status', 1)
                    ->selectRaw('DATE_FORMAT(outflows.set_date, "%Y-%m") as month')
                    ->selectRaw('COALESCE(SUM(outflows.amount), 0) as expense')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');

                $allMonths = array_unique(
                    array_merge(
                        $monthlyIncome->pluck('month')->toArray(),
                        $monthlyOutflow->pluck('month')->toArray()
                    )
                );
                sort($allMonths);

                $history = [];
                $balance = 0;

                foreach ($allMonths as $month) {
                    $income = round((float) ($monthlyIncome->get($month)?->income ?? 0), 2);
                    $expense = round((float) ($monthlyOutflow->get($month)?->expense ?? 0), 2);
                    $balance = round($balance + $income - $expense, 2);

                    $history[] = [
                        'date' => $month,
                        'income' => $income,
                        'expense' => $expense,
                        'balance' => $balance,
                    ];
                }

                return [
                    'id' => $deposit->id_porcent,
                    'name' => $deposit->name,
                    'history' => $history,
                ];
            })->toArray();

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($formatted, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_deposits_history', ['idUser' => $idUser]);
    }
}