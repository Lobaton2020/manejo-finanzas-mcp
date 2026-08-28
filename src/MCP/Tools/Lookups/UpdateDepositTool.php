<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateDepositTool extends BaseTool
{
    #[McpTool(
        name: 'update_deposit',
        description: 'Actualiza nombre y/o estado de un deposito. Solo si pertenece al idUser.'
    )]
    public function updateDeposit(int $idPorcent, int $idUser = 1, ?string $name = null, ?int $status = null): array
    {
        return $this->executeWithLogging(function () use ($idPorcent, $idUser, $name, $status) {
            $this->debug('updateDeposit start', ['idPorcent' => $idPorcent, 'idUser' => $idUser, 'name' => $name, 'status' => $status]);

            $existing = $this->table('porcents')->where('id_porcent', $idPorcent)->first();
            if (!$existing) {
                return $this->validationError('El deposito no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El deposito no pertenece al usuario.');
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

            $this->table('porcents')->where('id_porcent', $idPorcent)->update($data);
            $updated = $this->table('porcents')->where('id_porcent', $idPorcent)->first();

            $this->debug('updateDeposit updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_porcent' => (int) $updated->id_porcent,
                'id_user'    => (int) $updated->id_user,
                'name'       => $updated->name,
                'status'     => (int) $updated->status,
            ], 'Deposito actualizado.');
        }, 'update_deposit', [
            'idPorcent' => $idPorcent,
            'idUser'    => $idUser,
            'name'      => $name,
            'status'    => $status,
        ]);
    }
}