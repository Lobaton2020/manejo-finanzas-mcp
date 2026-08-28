<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class DisableDepositTool extends BaseTool
{
    #[McpTool(
        name: 'disable_deposit',
        description: 'Desactiva un deposito (status=0). Solo si pertenece al idUser.'
    )]
    public function disableDeposit(int $idPorcent, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idPorcent, $idUser) {
            $this->debug('disableDeposit start', ['idPorcent' => $idPorcent, 'idUser' => $idUser]);

            $existing = $this->table('porcents')->where('id_porcent', $idPorcent)->first();
            if (!$existing) {
                return $this->validationError('El deposito no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El deposito no pertenece al usuario.');
            }

            $this->table('porcents')->where('id_porcent', $idPorcent)->update(['status' => 0]);

            $this->debug('disableDeposit disabled', ['id_porcent' => $idPorcent]);

            return $this->successResponse([
                'id_porcent' => $idPorcent,
                'status'     => 0,
            ], 'Deposito desactivado.');
        }, 'disable_deposit', [
            'idPorcent' => $idPorcent,
            'idUser'    => $idUser,
        ]);
    }
}