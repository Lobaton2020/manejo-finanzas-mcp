<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetDepositsHistoryTool extends BaseTool
{
    #[McpTool(name: 'get_deposits_history', description: 'Obtiene el historial global de ingresos y egresos por mes. Devuelve totales mensuales de ingresos (excluyendo retornos de inversión) y egresos (solo los en presupuesto).')]
    public function getDepositsHistory(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            // Get monthly inflows (excluding type 8 - Retorno inversión)
            $inflows = $this->table('inflows')
                ->where('inflows.id_user', $idUser)
                ->where('inflows.status', 1)
                ->where('inflows.id_inflow_type', '!=', 8)
                ->selectRaw('DATE_FORMAT(inflows.set_date, "%Y-%m") as month')
                ->selectRaw('COALESCE(SUM(inflows.total), 0) as total_income')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month')
                ->toArray();

            // Get monthly outflows (only is_in_budget = 1)
            $outflows = $this->table('outflows')
                ->where('outflows.id_user', $idUser)
                ->where('outflows.status', 1)
                ->where('outflows.is_in_budget', 1)
                ->selectRaw('DATE_FORMAT(outflows.set_date, "%Y-%m") as month')
                ->selectRaw('COALESCE(SUM(outflows.amount), 0) as total_expense')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month')
                ->toArray();

            // Get all unique months
            $allMonths = array_unique(array_merge(
                array_keys($inflows),
                array_keys($outflows)
            ));
            sort($allMonths);

            // Build result
            $history = [];
            $balance = 0;

            foreach ($allMonths as $month) {
                $income = isset($inflows[$month]) ? round((float) $inflows[$month]->total_income, 2) : 0;
                $expense = isset($outflows[$month]) ? round((float) $outflows[$month]->total_expense, 2) : 0;
                $balance = round($balance + $income - $expense, 2);

                $history[] = [
                    'date' => $month,
                    'income' => $income,
                    'expense' => $expense,
                    'balance' => $balance,
                ];
            }

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($history, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_deposits_history', ['idUser' => $idUser]);
    }
}