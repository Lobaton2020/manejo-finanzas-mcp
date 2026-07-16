<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetOutflowsByMonthTool extends BaseTool
{
    /**
     * Obtiene los egresos detallados de un mes específico.
     * 
     * ¿Para qué sirve?: Ver qué gastaste en un mes específico, con detalle de cada egreso.
     * 
     * Lógica:
     * 1. Filtra egresos por mes (formato YYYY-MM)
     * 2. JOIN con tablas relacionadas para traer nombres (tipo, categoría, depósito)
     * 3. Ordena por fecha descendente
     * 
     * Diferencia con get_deposits_history:
     *   - get_deposits_history: resume totales por mes (para gráficos/tendencias)
     *   - get_outflows_by_month: lista detallada de cada egreso
     * 
     * @param string $yearMonth Mes en formato YYYY-MM (ej: "2026-03")
     * @param int|null $idUser ID del usuario (default: 1)
     * @return array Egresos del mes con: id_outflow, amount, description, set_date, is_in_budget, outflow_type, category, deposit
     */
    #[McpTool(
        name: 'get_outflows_by_month',
        description: 'Obtiene la lista detallada de egresos de un mes específico. Incluye monto, descripción, fecha, tipo, categoría y depósito de cada egreso.'
    )]
    public function getOutflowsByMonth(
        string $yearMonth,
        ?int $idUser = 1
    ): array {
        return $this->executeWithLogging(function () use ($yearMonth, $idUser) {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El formato debe ser YYYY-MM (ej: 2026-03)'
                    ]
                ];
            }

            $outflows = $this->table('outflows')
                ->select([
                    'outflows.id_outflow',
                    'outflows.amount',
                    'outflows.description',
                    'outflows.set_date',
                    'outflows.is_in_budget',
                    'outflowtypes.name as outflow_type',
                    'categories.name as category',
                    'porcents.name as deposit'
                ])
                ->join('outflowtypes', 'outflows.id_outflow_type', '=', 'outflowtypes.id_outflow_type')
                ->join('categories', 'outflows.id_category', '=', 'categories.id_category')
                ->join('porcents', 'outflows.id_porcent', '=', 'porcents.id_porcent')
                ->where('outflows.id_user', $idUser)
                ->where('outflows.status', 1)
                ->whereRaw("DATE_FORMAT(outflows.set_date, '%Y-%m') = ?", [$yearMonth])
                ->orderBy('outflows.set_date', 'desc')
                ->get();

            if ($outflows->isEmpty()) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => json_encode([
                            'message' => "No se encontraron egresos para $yearMonth",
                            'outflows' => []
                        ], JSON_PRETTY_PRINT)
                    ]
                ];
            }

            $total = $outflows->sum('amount');

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode([
                        'month' => $yearMonth,
                        'total_outflows' => $total,
                        'count' => $outflows->count(),
                        'outflows' => $outflows->toArray()
                    ], JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_outflows_by_month', [
            'yearMonth' => $yearMonth,
            'idUser' => $idUser
        ]);
    }
}