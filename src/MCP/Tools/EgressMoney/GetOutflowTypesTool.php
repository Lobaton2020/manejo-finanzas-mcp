<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetOutflowTypesTool extends BaseTool
{
    /**
     * Get all active outflow types for the authenticated user.
     * Returns a list of expense categories (e.g., "Food", "Transportation", "Entertainment").
     * Filters by user ID and only returns types with status = 1 (active).
     * If no user-specific types exist, returns global types (id_user = null).
     */
    #[McpTool(name: 'get_outflow_types')]
    public function getOutflowTypes(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $types = $this->table('outflowtypes')
                ->where('status', 1)
                ->where('id_user', $idUser)
                ->orderBy('name')
                ->get();

            if ($types->isEmpty()) {
                $types = $this->table('outflowtypes')
                    ->where('status', 1)
                    ->whereNull('id_user')
                    ->orderBy('name')
                    ->get();
            }

            if ($types->isEmpty()) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'No hay tipos de egreso activos disponibles. Debe crear al menos uno.'
                    ]
                ];
            }

            $formatted = $types->map(function ($type) {
                return [
                    'id' => $type->id_outflow_type,
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
        }, 'get_outflow_types', ['idUser' => $idUser]);
    }
}
