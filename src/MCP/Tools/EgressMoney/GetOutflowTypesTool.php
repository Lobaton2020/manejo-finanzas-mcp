<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetOutflowTypesTool extends BaseTool
{
    /**
     * Obtiene los tipos de egreso activos.
     * 
     * ¿Para qué sirve?: Necesitas saber qué tipos de gastos existen antes de crear un egreso.
     * 
     * Lógica:
     * 1. Busca tipos de egreso del usuario (id_user = $idUser)
     * 2. Si no hay, busca tipos globales (id_user = null)
     * 3. Solo retorna los que tienen status = 1
     * 
     * Ejemplo de uso:
     *   - Antes de llamar a outflow_money, llama esta función para obtener idOutflowType válido
     * 
     * @param int $idUser ID del usuario (default: 1)
     * @return array Lista de tipos con: id, name, status
     */
    #[McpTool(
        name: 'get_outflow_types',
        description: 'Obtiene todos los tipos de egreso activos. Los tipos de egreso son categorías de gastos (ej: "Comida", "Transporte", "Entretenimiento"). Si no hay tipos del usuario, retorna tipos globales. Retorna: id, name, status.'
    )]
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
