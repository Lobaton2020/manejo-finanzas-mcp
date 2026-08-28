<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateTemporalBudgetTool extends BaseTool
{
    #[McpTool(
        name: 'update_temporal_budget',
        description: 'Actualiza nombre y/o descripcion de un presupuesto temporal. Solo si pertenece al idUser.'
    )]
    public function updateTemporalBudget(int $idTemporalBudget, int $idUser = 1, ?string $name = null, ?string $description = null): array
    {
        return $this->executeWithLogging(function () use ($idTemporalBudget, $idUser, $name, $description) {
            $this->debug('updateTemporalBudget start', compact('idTemporalBudget', 'idUser', 'name', 'description'));

            $existing = $this->table('temporal_budgets')->where('id_temporal_budget', $idTemporalBudget)->first();
            if (!$existing) {
                return $this->validationError('El presupuesto no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El presupuesto no pertenece al usuario.');
            }

            $data = [];
            if ($name !== null && $name !== '') { $data['name'] = $name; }
            if ($description !== null) { $data['description'] = $description; }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (name o description).');
            }

            $this->table('temporal_budgets')->where('id_temporal_budget', $idTemporalBudget)->update($data);
            $updated = $this->table('temporal_budgets')->where('id_temporal_budget', $idTemporalBudget)->first();

            $this->debug('updateTemporalBudget updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_temporal_budget' => (int) $updated->id_temporal_budget,
                'name'               => $updated->name,
                'description'        => $updated->description,
            ], 'Presupuesto actualizado.');
        }, 'update_temporal_budget', compact('idTemporalBudget', 'idUser', 'name', 'description'));
    }
}