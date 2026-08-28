<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateCategoryTool extends BaseTool
{
    #[McpTool(
        name: 'update_category',
        description: 'Actualiza nombre y/o estado de una categoria. Opcionalmente la reasigna a otro tipo de egreso. Solo si pertenece al idUser.'
    )]
    public function updateCategory(int $idCategory, int $idUser = 1, ?string $name = null, ?int $status = null, ?int $idOutflowType = null): array
    {
        return $this->executeWithLogging(function () use ($idCategory, $idUser, $name, $status, $idOutflowType) {
            $this->debug('updateCategory start', ['idCategory' => $idCategory, 'idUser' => $idUser, 'name' => $name, 'status' => $status, 'idOutflowType' => $idOutflowType]);

            $existing = $this->table('categories')->where('id_category', $idCategory)->first();
            if (!$existing) {
                return $this->validationError('La categoria no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('La categoria no pertenece al usuario.');
            }

            $data = [];
            if ($name !== null && $name !== '') {
                $data['name'] = $name;
            }
            if ($status !== null) {
                $data['status'] = $status;
            }
            if ($idOutflowType !== null) {
                $ot = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
                if (!$ot) {
                    return $this->validationError('El tipo de egreso destino no existe.');
                }
                $data['id_outflow_type'] = $idOutflowType;
            }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (name, status o idOutflowType).');
            }

            $this->table('categories')->where('id_category', $idCategory)->update($data);
            $updated = $this->table('categories')->where('id_category', $idCategory)->first();

            $this->debug('updateCategory updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_category'     => (int) $updated->id_category,
                'id_outflow_type' => (int) $updated->id_outflow_type,
                'id_user'         => (int) $updated->id_user,
                'name'            => $updated->name,
                'status'          => (int) $updated->status,
            ], 'Categoria actualizada.');
        }, 'update_category', [
            'idCategory'     => $idCategory,
            'idUser'         => $idUser,
            'name'           => $name,
            'status'         => $status,
            'idOutflowType'  => $idOutflowType,
        ]);
    }
}