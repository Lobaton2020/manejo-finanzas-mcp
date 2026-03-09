<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetCategoriesTool extends BaseTool
{
    /**
     * Get outflow categories. 
     * Depends on outflow type - use idOutflowType parameter to filter by type.
     */
    #[McpTool(name: 'get_categories', description: 'Get all active categories for outflows. Categories are dependent on outflow types - use idOutflowType parameter to filter. Returns category ID, name, and associated outflow type ID. Only returns categories with status = 1 (active).')]
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
