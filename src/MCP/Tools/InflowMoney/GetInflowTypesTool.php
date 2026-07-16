<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetInflowTypesTool extends BaseTool
{
    /**
     * Get all active inflow types for the authenticated user.
     * Returns a list of income categories (e.g., "Salary", "Investment", "Freelance").
     * Filters by user ID and only returns types with status = 1 (active).
     * If no user-specific types exist, returns global types (id_user = null).
     */
    #[McpTool(
        name: 'get_inflow_types',
        description: 'Obtiene todos los tipos de ingreso activos. Los tipos de ingreso son fuentes de dinero (ej: "Salario", "Inversión", "Freelance"). Si no hay tipos del usuario, retorna tipos globales. Retorna: id, name, status.'
    )]
    public function getInflowTypes(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $types = $this->table('inflowtypes')
                ->where('status', 1)
                ->where('id_user', $idUser)
                ->orderBy('name')
                ->get();

            if ($types->isEmpty()) {
                $types = $this->table('inflowtypes')
                    ->where('status', 1)
                    ->whereNull('id_user')
                    ->orderBy('name')
                    ->get();
            }

            if ($types->isEmpty()) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'No hay tipos de ingreso activos disponibles. Debe crear al menos uno.'
                    ]
                ];
            }

            $formatted = $types->map(function ($type) {
                return [
                    'id' => $type->id_inflow_type,
                    'name' => $type->name,
                    'status' => $type->status,
                ];
            })->toArray();

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($formatted, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_inflow_types', ['idUser' => $idUser]);
    }
}
