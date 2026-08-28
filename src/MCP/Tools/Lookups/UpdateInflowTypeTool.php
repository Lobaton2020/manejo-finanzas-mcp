<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateInflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'update_inflow_type',
        description: 'Actualiza nombre y/o estado de un tipo de ingreso. Solo si pertenece al idUser.'
    )]
    public function updateInflowType(int $idInflowType, int $idUser = 1, ?string $name = null, ?int $status = null): array
    {
        return $this->executeWithLogging(function () use ($idInflowType, $idUser, $name, $status) {
            $this->debug('updateInflowType start', ['idInflowType' => $idInflowType, 'idUser' => $idUser, 'name' => $name, 'status' => $status]);

            $existing = $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de ingreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de ingreso no pertenece al usuario.');
            }

            $data = [];
            if ($name !== null && $name !== '') {
                $data['name'] = $name;
            }
            if ($status !== null) {
                $data['status'] = $status;
            }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (name o status).');
            }

            $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->update($data);
            $updated = $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->first();

            $this->debug('updateInflowType updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_inflow_type' => (int) $updated->id_inflow_type,
                'id_user'        => (int) $updated->id_user,
                'name'           => $updated->name,
                'status'         => (int) $updated->status,
            ], 'Tipo de ingreso actualizado.');
        }, 'update_inflow_type', [
            'idInflowType' => $idInflowType,
            'idUser'       => $idUser,
            'name'         => $name,
            'status'       => $status,
        ]);
    }
}