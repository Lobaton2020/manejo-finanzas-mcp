<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateOutflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'create_outflow_type',
        description: 'Crea un nuevo tipo de egreso (categoria de egreso) para el usuario. Parametros requeridos: name. Opcionales: idUser (default 1), status (default 1). Retorna el id generado.'
    )]
    public function createOutflowType(string $name, int $idUser = 1, int $status = 1): array
    {
        return $this->executeWithLogging(function () use ($name, $idUser, $status) {
            $this->debug('createOutflowType start', ['idUser' => $idUser, 'name' => $name, 'status' => $status]);

            if (empty(trim($name))) {
                $this->debug('createOutflowType validation failed: empty name');
                return $this->validationError('El nombre del tipo de egreso es requerido.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('outflowtypes')->insertGetId([
                'id_user'   => $idUser,
                'name'      => $name,
                'status'    => $status,
                'create_at' => $now,
            ]);

            $this->debug('createOutflowType inserted', ['id_outflow_type' => $id]);

            return $this->successResponse([
                'id_outflow_type' => (int) $id,
                'id_user'         => $idUser,
                'name'            => $name,
                'status'          => $status,
            ], 'Tipo de egreso creado exitosamente.');
        }, 'create_outflow_type', [
            'name'   => $name,
            'idUser' => $idUser,
            'status' => $status,
        ]);
    }
}