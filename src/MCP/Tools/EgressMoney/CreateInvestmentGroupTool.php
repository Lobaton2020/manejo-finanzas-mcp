<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateInvestmentGroupTool extends BaseTool
{
    #[McpTool(
        name: 'create_investment_group',
        description: 'Crea un grupo de inversion. Parametros requeridos: name. Opcionales: idUser (default 1), description.'
    )]
    public function createInvestmentGroup(string $name, int $idUser = 1, ?string $description = null): array
    {
        return $this->executeWithLogging(function () use ($name, $idUser, $description) {
            $this->debug('createInvestmentGroup start', compact('idUser', 'name', 'description'));

            if (empty(trim($name))) {
                return $this->validationError('El nombre del grupo es requerido.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('group_investments')->insertGetId([
                'id_user'     => $idUser,
                'name'        => $name,
                'description' => $description,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $this->debug('createInvestmentGroup inserted', ['id_group_investment' => $id]);

            return $this->successResponse([
                'id_group_investment' => (int) $id,
                'id_user'             => $idUser,
                'name'                => $name,
                'description'         => $description,
            ], 'Grupo creado.');
        }, 'create_investment_group', compact('name', 'idUser', 'description'));
    }
}