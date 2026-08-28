<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetExpenseForecastTool extends BaseTool
{
    #[McpTool(
        name: 'get_expense_forecast',
        description: 'Proyecta los gastos de los próximos 6 meses usando promedio estacional (mismo mes de años anteriores). Útil para planificación presupuestal.'
    )]
    public function getExpenseForecast(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('getExpenseForecast start', compact('idUser'));

            $cutoff = date('Y-m-d', strtotime('-24 months'));

            $rows = $this->table('outflows')
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->where('is_in_budget', 1)
                ->where('set_date', '>=', $cutoff)
                ->select('set_date', 'amount')
                ->get()
                ->toArray();

            if (empty($rows)) {
                $this->debug('getExpenseForecast no data');
                return ['content' => ['type' => 'text', 'text' => 'No hay datos']];
            }

            $monthTotals = [];
            $monthCounts = [];
            foreach ($rows as $row) {
                $m = (int) substr((string) $row->set_date, 5, 2);
                if (!isset($monthTotals[$m])) {
                    $monthTotals[$m] = 0.0;
                    $monthCounts[$m] = 0;
                }
                $monthTotals[$m] += (float) $row->amount;
                $monthCounts[$m]++;
            }
            foreach ($monthTotals as $m => $total) {
                $monthTotals[$m] = $total / $monthCounts[$m];
            }

            $lastMonth = (int) date('m');
            $lastYear = (int) date('Y');
            $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

            $overallAvg = array_sum($monthTotals) / count($monthTotals);

            $forecast = [];
            for ($i = 1; $i <= 6; $i++) {
                $nextM = ($lastMonth + $i - 1) % 12 + 1;
                $nextY = $lastYear + intdiv($lastMonth + $i - 1, 12);
                $proj = $monthTotals[$nextM] ?? $overallAvg;
                $forecast[] = [
                    'month' => sprintf('%d-%02d', $nextY, $nextM),
                    'name' => $meses[$nextM - 1],
                    'projected' => round($proj, 2),
                ];
            }

            $total = 0.0;
            foreach ($forecast as $f) { $total += $f['projected']; }

            $this->debug('getExpenseForecast projected', ['months' => count($forecast), 'total' => $total]);

            return ['content' => ['type' => 'text', 'text' => json_encode(['forecast' => $forecast, 'total' => round($total, 2), 'method' => 'seasonal_avg'], JSON_PRETTY_PRINT)]];
        }, 'get_expense_forecast', ['idUser' => $idUser]);
    }
}