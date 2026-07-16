<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetInflowTypesTool extends BaseTool
{
    /**
     * Obtiene los tipos de ingreso activos.
     * 
     * ¿Para qué sirve?: Necesitas saber qué fuentes de ingreso existen antes de registrar un ingreso.
     * 
     * Lógica:
     * 1. Busca tipos de ingreso del usuario (id_user = $idUser)
     * 2. Si no hay, busca tipos globales (id_user = null)
     * 3. Solo retorna los que tienen status = 1
     * 
     * Ejemplo de uso:
     *   - Antes de llamar a inflow_money, llama esta función para obtener idInflowType válido
     * 
     * @param int $idUser ID del usuario (default: 1)
     * @return array Lista de tipos con: id, name, status
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
