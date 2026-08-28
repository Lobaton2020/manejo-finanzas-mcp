<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateInvestmentGroupTool extends BaseTool
{
    #[McpTool(
        name: 'update_investment_group',
        description: 'Actualiza nombre y/o descripcion de un grupo de inversion. Solo si pertenece al idUser.'
    )]
    public function updateInvestmentGroup(int $idGroupInvestment, int $idUser = 1, ?string $name = null, ?string $description = null): array
    {
        return $this->executeWithLogging(function () use ($idGroupInvestment, $idUser, $name, $description) {
            $this->debug('updateInvestmentGroup start', compact('idGroupInvestment', 'idUser', 'name', 'description'));

            $existing = $this->table('group_investments')->where('id_group_investment', $idGroupInvestment)->first();
            if (!$existing) {
                return $this->validationError('El grupo no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El grupo no pertenece al usuario.');
            }

            $data = [];
            if ($name !== null && $name !== '') { $data['name'] = $name; }
            if ($description !== null) { $data['description'] = $description; }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (name o description).');
            }

            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->table('group_investments')->where('id_group_investment', $idGroupInvestment)->update($data);
            $updated = $this->table('group_investments')->where('id_group_investment', $idGroupInvestment)->first();

            $this->debug('updateInvestmentGroup updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_group_investment' => (int) $updated->id_group_investment,
                'name'                => $updated->name,
                'description'         => $updated->description,
                'updated_at'          => $updated->updated_at,
            ], 'Grupo actualizado.');
        }, 'update_investment_group', compact('idGroupInvestment', 'idUser', 'name', 'description'));
    }
}