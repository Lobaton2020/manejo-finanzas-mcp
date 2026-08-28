<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateTemporalBudgetTool extends BaseTool
{
    #[McpTool(
        name: 'create_temporal_budget',
        description: 'Crea un presupuesto temporal. Parametros requeridos: name. Opcionales: idUser (default 1), description.'
    )]
    public function createTemporalBudget(string $name, int $idUser = 1, ?string $description = null): array
    {
        return $this->executeWithLogging(function () use ($name, $idUser, $description) {
            $this->debug('createTemporalBudget start', compact('idUser', 'name', 'description'));

            if (empty(trim($name))) {
                return $this->validationError('El nombre del presupuesto es requerido.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('temporal_budgets')->insertGetId([
                'id_user'     => $idUser,
                'name'        => $name,
                'description' => $description,
                'created_at'  => $now,
            ]);

            $this->debug('createTemporalBudget inserted', ['id_temporal_budget' => $id]);

            return $this->successResponse([
                'id_temporal_budget' => (int) $id,
                'id_user'            => $idUser,
                'name'               => $name,
                'description'        => $description,
                'created_at'         => $now,
            ], 'Presupuesto creado.');
        }, 'create_temporal_budget', compact('name', 'idUser', 'description'));
    }
}