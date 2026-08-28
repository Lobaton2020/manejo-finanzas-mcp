<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateTemporalBudgetOutflowTool extends BaseTool
{
    #[McpTool(
        name: 'update_temporal_budget_outflow',
        description: 'Actualiza amount y/o description de un item del presupuesto temporal. Solo si pertenece al idUser.'
    )]
    public function updateTemporalBudgetOutflow(
        int $idTemporalBudgetOutflow,
        int $idUser = 1,
        ?float $amount = null,
        ?string $description = null
    ): array {
        return $this->executeWithLogging(function () use ($idTemporalBudgetOutflow, $idUser, $amount, $description) {
            $this->debug('updateTemporalBudgetOutflow start', compact('idTemporalBudgetOutflow', 'idUser', 'amount', 'description'));

            $row = $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->first();
            if (!$row) {
                return $this->validationError('El item no existe.');
            }
            if ((int) $row->id_user !== $idUser) {
                return $this->validationError('El item no pertenece al usuario.');
            }

            $data = [];
            if ($amount !== null) {
                if ($amount <= 0) {
                    return $this->validationError('El monto debe ser mayor a 0.');
                }
                $data['amount'] = $amount;
            }
            if ($description !== null) { $data['description'] = $description; }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (amount o description).');
            }

            $data['update_at'] = date('Y-m-d H:i:s');
            $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->update($data);
            $updated = $this->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $idTemporalBudgetOutflow)->first();

            $this->debug('updateTemporalBudgetOutflow updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_temporal_budget_outflow' => (int) $updated->id_temporal_budget_outflow,
                'amount'                     => (float) $updated->amount,
                'description'                => $updated->description,
            ], 'Item actualizado.');
        }, 'update_temporal_budget_outflow', compact('idTemporalBudgetOutflow', 'idUser', 'amount', 'description'));
    }
}