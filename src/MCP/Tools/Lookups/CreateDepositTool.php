<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateDepositTool extends BaseTool
{
    #[McpTool(
        name: 'create_deposit',
        description: 'Crea un deposito/porcent para el usuario. Parametros requeridos: name. Opcionales: idUser (default 1), status (default 1).'
    )]
    public function createDeposit(string $name, int $idUser = 1, int $status = 1): array
    {
        return $this->executeWithLogging(function () use ($name, $idUser, $status) {
            $this->debug('createDeposit start', ['idUser' => $idUser, 'name' => $name, 'status' => $status]);

            if (empty(trim($name))) {
                return $this->validationError('El nombre del deposito es requerido.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('porcents')->insertGetId([
                'id_user'   => $idUser,
                'name'      => $name,
                'status'    => $status,
                'create_at' => $now,
            ]);

            $this->debug('createDeposit inserted', ['id_porcent' => $id]);

            return $this->successResponse([
                'id_porcent' => (int) $id,
                'id_user'    => $idUser,
                'name'       => $name,
                'status'     => $status,
            ], 'Deposito creado exitosamente.');
        }, 'create_deposit', [
            'name'   => $name,
            'idUser' => $idUser,
            'status' => $status,
        ]);
    }
}