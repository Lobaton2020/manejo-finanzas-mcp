<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetInvestmentGroupsTool extends BaseTool
{
    /**
     * List the investment groups registered by a user.
     * Mirrors the dropdown shown in the outflow creation form (only rendered when
     * the outflow type contains "inversion"). Groups are ordered by name ASC and
     * optionally include the count of investments currently linked to each group.
     * Parameters:
     *   - idUser (optional): User ID (default: 1)
     *   - includeInvestmentCount (optional): Include investment_count per group (default: false)
     * Returns the list of groups with id_group_investment, name, description, created_at, updated_at.
     */
    #[McpTool(
        name: 'get_investment_groups',
        description: 'Lista los grupos de inversion del usuario. Devuelve id_group_investment, name, description, created_at y updated_at. Parametros: idUser (opcional, default 1), includeInvestmentCount (opcional, default false) para incluir el conteo de inversiones asociadas a cada grupo.'
    )]
    public function getInvestmentGroups(
        int $idUser = 1,
        bool $includeInvestmentCount = false
    ): array {
        return $this->executeWithLogging(function () use ($idUser, $includeInvestmentCount) {
            $user = $this->table('users')
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->first();

            if (!$user) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El usuario no existe o esta inactivo.'
                    ]
                ];
            }

            $query = $this->table('group_investments')
                ->where('id_user', $idUser)
                ->orderBy('name', 'ASC');

            if ($includeInvestmentCount) {
                $query->selectRaw(
                    'group_investments.*, (
                        SELECT COUNT(*)
                        FROM investments
                        WHERE investments.id_group_investment = group_investments.id_group_investment
                    ) AS investment_count'
                );
            }

            $groups = $query->get()->map(function ($g) {
                return [
                    'id_group_investment' => (int) $g->id_group_investment,
                    'name' => $g->name,
                    'description' => $g->description,
                    'created_at' => $g->created_at,
                    'updated_at' => $g->updated_at,
                    'investment_count' => isset($g->investment_count) ? (int) $g->investment_count : null,
                ];
            })->all();

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode([
                        'success' => true,
                        'count' => count($groups),
                        'groups' => $groups,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ]
            ];
        }, 'get_investment_groups', [
            'idUser' => $idUser,
            'includeInvestmentCount' => $includeInvestmentCount,
        ]);
    }
}
