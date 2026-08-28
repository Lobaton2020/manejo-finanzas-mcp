<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class EnableTemporalBudgetOutflowTool extends BaseTool
{
    #[McpTool(
        name: 'enable_temporal_budget_outflow',
        description: 'Activa un item del presupuesto temporal (status=1). Solo si pertenece al idUser.'
    )]
    public function enableTemporalBudgetOutflow(int $idTemporalBudgetOutflow, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idTemporalBudgetOutflow, $idUser) {
            $this->debug('enableTemporalBudgetOutflow start', compact('idTemporalBudgetOutflow', 'idUser'));

            $row = $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->first();
            if (!$row) {
                return $this->validationError('El item no existe.');
            }
            if ((int) $row->id_user !== $idUser) {
                return $this->validationError('El item no pertenece al usuario.');
            }

            $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->update(['status' => 1]);

            $this->debug('enableTemporalBudgetOutflow enabled', ['id_temporal_budget_outflow' => $idTemporalBudgetOutflow]);

            return $this->successResponse([
                'id_temporal_budget_outflow' => $idTemporalBudgetOutflow,
                'status'                     => 1,
            ], 'Item activado.');
        }, 'enable_temporal_budget_outflow', compact('idTemporalBudgetOutflow', 'idUser'));
    }
}