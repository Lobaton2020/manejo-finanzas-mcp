<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetExpenseForecastTool extends BaseTool
{
    /**
     * Proyecta los gastos de los próximos 6 meses.
     * 
     * ¿Para qué sirve?: Planificar presupuesto futuro basándote en patrones históricos.
     * 
     * Lógica:
     * 1. Obtiene los últimos 24 meses de egresos (solo is_in_budget = 1)
     * 2. Calcula el promedio por mes (ej: promedio de todos los enero, todos los febrero, etc.)
     * 3. Proyecta los próximos 6 meses usando esos promedios
     * 4. Si no hay dato histórico para un mes, usa el promedio general
     * 
     * Método: "seasonal_avg" (promedio estacional)
     * 
     * Limitaciones:
     * - Solo usa egresos marcados como "en presupuesto"
     * - No considera gastos nuevos o cambios de patrón
     * - Asume comportamiento futuro similar al histórico
     * 
     * @param int $idUser ID del usuario (default: 1)
     * @return array Proyección con: forecast (lista por mes), total (suma 6 meses), method
     */
    #[McpTool(
        name: 'get_expense_forecast',
        description: 'Proyecta los gastos de los próximos 6 meses usando promedio estacional (mismo mes de años anteriores). Útil para planificación presupuestal.'
    )]
    public function getExpenseForecast(int $idUser = 1): array
    {
        // Get monthly totals for last 24 months, then average
        $monthlyTotals = $this->table('outflows')
            ->where('id_user', $idUser)
            ->where('status', 1)
            ->where('is_in_budget', 1)
            ->whereRaw('set_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)')
            ->selectRaw('YEAR(set_date) as anio, MONTH(set_date) as mes, SUM(amount) as total')
            ->groupBy('anio', 'mes')
            ->orderBy('mes')
            ->get()
            ->toArray();

        if (empty($monthlyTotals)) {
            return ['content' => ['type' => 'text', 'text' => 'No hay datos']];
        }

        // Group by month and calculate average
        $monthAvg = [];
        $monthCount = [];
        foreach ($monthlyTotals as $row) {
            $m = (int)$row->mes;
            if (!isset($monthAvg[$m])) {
                $monthAvg[$m] = 0;
                $monthCount[$m] = 0;
            }
            $monthAvg[$m] += $row->total;
            $monthCount[$m]++;
        }
        foreach ($monthAvg as $m => $total) {
            $monthAvg[$m] = $total / $monthCount[$m];
        }

        $lastMonth = (int)date('m');
        $lastYear = (int)date('Y');
        $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        
        $forecast = [];
        for ($i = 1; $i <= 6; $i++) {
            $nextM = ($lastMonth + $i - 1) % 12 + 1;
            $nextY = $lastYear + floor(($lastMonth + $i - 1) / 12);
            
            $proj = $monthAvg[$nextM] ?? array_sum($monthAvg) / count($monthAvg);
            
            $forecast[] = [
                'month' => sprintf('%d-%02d', $nextY, $nextM),
                'name' => $meses[$nextM - 1],
                'projected' => round($proj, 2)
            ];
        }

        $total = array_sum(array_column($forecast, 'projected'));

        return ['content' => ['type' => 'text', 'text' => json_encode(['forecast' => $forecast, 'total' => round($total, 2), 'method' => 'seasonal_avg'], JSON_PRETTY_PRINT)]];
    }
}
