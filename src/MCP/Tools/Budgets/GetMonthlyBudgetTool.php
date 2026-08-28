<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetMonthlyBudgetTool extends BaseTool
{
    #[McpTool(
        name: 'get_monthly_budget',
        description: 'Obtiene el budget mensual del usuario para el mes actual desde view_budget. Devuelve id_budget, total presupuestado, total gastado (en presupuesto), restante y porcentaje usado.'
    )]
    public function getMonthlyBudget(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('getMonthlyBudget start', compact('idUser'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $row = $this->table('budget')
                ->where('id_user', $idUser)
                ->orderBy('created_at', 'DESC')
                ->first();

            if (!$row) {
                $this->debug('getMonthlyBudget no data');
                return $this->successResponse([
                    'has_budget' => false,
                    'message'    => 'No hay budget configurado para este usuario.',
                ], 'Sin budget.');
            }

            $this->debug('getMonthlyBudget found', ['id_budget' => $row->id_budget ?? null]);

            $totalOutflow = (float) ($this->table('outflows')
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->sum('amount') ?? 0);
            $budget = (float) $row->total;
            $remain = round($budget - $totalOutflow, 2);
            $percent = $budget > 0 ? round(($totalOutflow / $budget) * 100, 2) : 0.0;

            return $this->successResponse([
                'has_budget' => true,
                'id_budget'  => (int) $row->id_budget,
                'budget'     => $budget,
                'total'      => $totalOutflow,
                'remain'     => $remain,
                'percent'    => $percent,
                'date'       => $row->created_at,
            ], 'Budget mensual.');
        }, 'get_monthly_budget', compact('idUser'));
    }
}