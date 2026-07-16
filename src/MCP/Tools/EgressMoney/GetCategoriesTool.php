<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetCategoriesTool extends BaseTool
{
    /**
     * Obtiene las categorías de egreso.
     * 
     * ¿Para qué sirve?: Las categorías dependen del tipo de egreso. Por ejemplo, 
     * si el tipo es "Gastos", las categorías pueden ser "Comida", "Transporte", etc.
     * Si el tipo es "Inversión", las categorías pueden ser "Acciones", "Bienes raíces", etc.
     * 
     * Lógica:
     * 1. Si se pasa idOutflowType, filtra solo categorías de ese tipo
     * 2. Solo retorna las que tienen status = 1
     * 
     * Importante: Una categoría pertenece a un tipo de egreso específico.
     * No puedes usar cualquier categoría con cualquier tipo.
     * 
     * Ejemplo de uso:
     *   1. Llama get_outflow_types para obtener los tipos
     *   2. Llama get_categories con el idOutflowType elegido
     *   3. Usa el idCategory retornado para outflow_money
     * 
     * @param int|null $idOutflowType Filtrar por tipo de egreso (opcional)
     * @return array Lista de categorías con: id, name, type_id
     */
    #[McpTool(name: 'get_categories', description: 'Obtiene las categorías de egreso. Las categorías dependen del tipo de egreso - usa idOutflowType para filtrar. Retorna: id, name, type_id. Solo status=1.')]
    public function getCategories(?int $idOutflowType = null): array
    {
        return $this->executeWithLogging(function () use ($idOutflowType) {
            $query = $this->table('categories')
                ->where('status', 1);

            if ($idOutflowType !== null) {
                $query->where('id_outflow_type', $idOutflowType);
            }

            $categories = $query->orderBy('name')->get();

            if ($categories->isEmpty()) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'No hay categorías disponibles.'
                    ]
                ];
            }

            $formatted = $categories->map(function ($cat) {
                return [
                    'id' => $cat->id_category,
                    'name' => $cat->name,
                    'type_id' => $cat->id_outflow_type,
                ];
            })->toArray();

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode($formatted, JSON_PRETTY_PRINT)
                ]
            ];
        }, 'get_categories', ['idOutflowType' => $idOutflowType]);
    }
}
