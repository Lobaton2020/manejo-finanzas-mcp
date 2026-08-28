<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateCategoryTool extends BaseTool
{
    #[McpTool(
        name: 'create_category',
        description: 'Crea una categoria de egreso asociada a un tipo de egreso. Parametros requeridos: idOutflowType, name. Opcionales: idUser (default 1), status (default 1).'
    )]
    public function createCategory(int $idOutflowType, string $name, int $idUser = 1, int $status = 1): array
    {
        return $this->executeWithLogging(function () use ($idOutflowType, $name, $idUser, $status) {
            $this->debug('createCategory start', ['idOutflowType' => $idOutflowType, 'name' => $name, 'idUser' => $idUser]);

            if (empty(trim($name))) {
                return $this->validationError('El nombre de la categoria es requerido.');
            }

            $outflowType = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
            if (!$outflowType) {
                return $this->validationError('El tipo de egreso no existe.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('categories')->insertGetId([
                'id_user'         => $idUser,
                'id_outflow_type' => $idOutflowType,
                'name'            => $name,
                'status'          => $status,
                'create_at'       => $now,
            ]);

            $this->debug('createCategory inserted', ['id_category' => $id]);

            return $this->successResponse([
                'id_category'     => (int) $id,
                'id_outflow_type' => $idOutflowType,
                'id_user'         => $idUser,
                'name'            => $name,
                'status'          => $status,
            ], 'Categoria creada exitosamente.');
        }, 'create_category', [
            'idOutflowType' => $idOutflowType,
            'name'          => $name,
            'idUser'        => $idUser,
            'status'        => $status,
        ]);
    }
}