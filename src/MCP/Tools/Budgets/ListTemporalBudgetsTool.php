<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListTemporalBudgetsTool extends BaseTool
{
    #[McpTool(
        name: 'list_temporal_budgets',
        description: 'Lista los presupuestos temporales del usuario desde view_temporal_budgets. Devuelve id, name, description, created_at y total_amount.'
    )]
    public function listTemporalBudgets(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('listTemporalBudgets start', compact('idUser'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $budgets = $this->table('temporal_budgets')
                ->where('id_user', $idUser)
                ->orderBy('created_at', 'DESC')
                ->get();

            $rows = $budgets->map(function($b) {
                $total = (float) ($this->table('temporal_budgets_outflow')
                    ->where('id_temporal_budget', $b->id_temporal_budget)
                    ->where('status', 1)
                    ->sum('amount') ?? 0);
                return [
                    'id_temporal_budget' => (int) $b->id_temporal_budget,
                    'name'               => $b->name,
                    'description'        => $b->description,
                    'created_at'         => $b->created_at,
                    'total_amount'       => $total,
                ];
            })->all();

            $this->debug('listTemporalBudgets result', ['count' => count($rows)]);

            return $this->listResponse($rows, 'budgets');
        }, 'list_temporal_budgets', compact('idUser'));
    }
}