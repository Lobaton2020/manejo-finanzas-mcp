<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateInflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'create_inflow_type',
        description: 'Crea un nuevo tipo de ingreso para el usuario. Parametros requeridos: name. Opcionales: idUser (default 1), status (default 1). Retorna el id generado.'
    )]
    public function createInflowType(string $name, int $idUser = 1, int $status = 1): array
    {
        return $this->executeWithLogging(function () use ($name, $idUser, $status) {
            $this->debug('createInflowType start', ['idUser' => $idUser, 'name' => $name, 'status' => $status]);

            if (empty(trim($name))) {
                return $this->validationError('El nombre del tipo de ingreso es requerido.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('inflowtypes')->insertGetId([
                'id_user'   => $idUser,
                'name'      => $name,
                'status'    => $status,
                'create_at' => $now,
            ]);

            $this->debug('createInflowType inserted', ['id_inflow_type' => $id]);

            return $this->successResponse([
                'id_inflow_type' => (int) $id,
                'id_user'        => $idUser,
                'name'           => $name,
                'status'         => $status,
            ], 'Tipo de ingreso creado exitosamente.');
        }, 'create_inflow_type', [
            'name'   => $name,
            'idUser' => $idUser,
            'status' => $status,
        ]);
    }
}