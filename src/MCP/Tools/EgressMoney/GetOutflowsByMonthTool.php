<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetOutflowsByMonthTool extends BaseTool
{
    #[McpTool(
        name: 'get_outflows_by_month',
        description: 'Obtiene la lista detallada de egresos de un mes específico. Incluye monto, descripción, fecha, tipo, categoría y depósito de cada egreso.'
    )]
    public function getOutflowsByMonth(
        string $yearMonth,
        ?int $idUser = 1
    ): array {
        return $this->executeWithLogging(function () use ($yearMonth, $idUser) {
            $this->debug('getOutflowsByMonth start', compact('yearMonth', 'idUser'));

            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El formato debe ser YYYY-MM (ej: 2026-03)'
                    ]
                ];
            }

            $rows = $this->table('outflows')
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
                ->where('outflows.set_date', 'like', $yearMonth . '%')
                ->orderBy('outflows.set_date', 'desc')
                ->get();

            $outflows = $rows->map(function ($r) {
                return [
                    'id_outflow'   => (int) $r->id_outflow,
                    'amount'       => (float) $r->amount,
                    'description'  => $r->description,
                    'set_date'     => $r->set_date,
                    'is_in_budget' => (int) $r->is_in_budget,
                    'outflow_type' => $r->outflow_type,
                    'category'     => $r->category,
                    'deposit'      => $r->deposit,
                ];
            })->all();

            if (empty($outflows)) {
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

            $total = 0.0;
            foreach ($outflows as $o) { $total += $o['amount']; }

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode([
                        'month' => $yearMonth,
                        'total_outflows' => $total,
                        'count' => count($outflows),
                        'outflows' => $outflows
                    ], JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_outflows_by_month', [
            'yearMonth' => $yearMonth,
            'idUser' => $idUser
        ]);
    }
}