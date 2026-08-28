<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateOutflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'update_outflow_type',
        description: 'Actualiza el nombre y/o estado de un tipo de egreso. Parametros requeridos: idOutflowType. Opcionales: name, status. Solo modifica el tipo si pertenece al idUser.'
    )]
    public function updateOutflowType(int $idOutflowType, int $idUser = 1, ?string $name = null, ?int $status = null): array
    {
        return $this->executeWithLogging(function () use ($idOutflowType, $idUser, $name, $status) {
            $this->debug('updateOutflowType start', ['idOutflowType' => $idOutflowType, 'idUser' => $idUser, 'name' => $name, 'status' => $status]);

            $existing = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de egreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de egreso no pertenece al usuario.');
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

            $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->update($data);
            $updated = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();

            $this->debug('updateOutflowType updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_outflow_type' => (int) $updated->id_outflow_type,
                'id_user'         => (int) $updated->id_user,
                'name'            => $updated->name,
                'status'          => (int) $updated->status,
            ], 'Tipo de egreso actualizado.');
        }, 'update_outflow_type', [
            'idOutflowType' => $idOutflowType,
            'idUser'        => $idUser,
            'name'          => $name,
            'status'        => $status,
        ]);
    }
}