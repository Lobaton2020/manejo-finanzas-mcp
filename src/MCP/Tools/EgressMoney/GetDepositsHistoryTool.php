<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetDepositsHistoryTool extends BaseTool
{
    /**
     * Obtiene el historial mensual de ingresos vs egresos.
     * ¿Para qué sirve?: Ver la tendencia financiera mes a mes.
     * - Agrupa ingresos por mes (excluye tipo 8 = Retorno inversión)
     * - Agrupa egresos por mes (solo is_in_budget = 1)
     * - Calcula balance acumulado
     *
     * @param int $idUser ID del usuario (default: 1)
     * @return array Lista de meses con: date, income, expense, balance
     */
    #[McpTool(name: 'get_deposits_history', description: 'Obtiene el historial mensual resumido: ingresos totales, egresos en presupuesto, y balance acumulado por mes. Útil para gráficos y tendencias.')]
    public function getDepositsHistory(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('getDepositsHistory start', compact('idUser'));

            $inflows = $this->table('inflows')
                ->where('inflows.id_user', $idUser)
                ->where('inflows.status', 1)
                ->where('inflows.id_inflow_type', '!=', 8)
                ->select('inflows.set_date', 'inflows.total')
                ->get()
                ->toArray();

            $outflows = $this->table('outflows')
                ->where('outflows.id_user', $idUser)
                ->where('outflows.status', 1)
                ->where('outflows.is_in_budget', 1)
                ->select('outflows.set_date', 'outflows.amount')
                ->get()
                ->toArray();

            $byMonth = [];
            foreach ($inflows as $row) {
                $month = substr((string) $row->set_date, 0, 7);
                $byMonth[$month]['income'] = ($byMonth[$month]['income'] ?? 0) + (float) $row->total;
            }
            foreach ($outflows as $row) {
                $month = substr((string) $row->set_date, 0, 7);
                $byMonth[$month]['expense'] = ($byMonth[$month]['expense'] ?? 0) + (float) $row->amount;
            }
            ksort($byMonth);

            $history = [];
            $balance = 0.0;
            foreach ($byMonth as $month => $values) {
                $income = round((float) ($values['income'] ?? 0), 2);
                $expense = round((float) ($values['expense'] ?? 0), 2);
                $balance = round($balance + $income - $expense, 2);
                $history[] = [
                    'date'    => $month,
                    'income'  => $income,
                    'expense' => $expense,
                    'balance' => $balance,
                ];
            }

            $this->debug('getDepositsHistory result', ['months' => count($history)]);

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($history, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_deposits_history', ['idUser' => $idUser]);
    }
}