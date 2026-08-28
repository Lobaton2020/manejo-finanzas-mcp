<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class EnableDepositTool extends BaseTool
{
    #[McpTool(
        name: 'enable_deposit',
        description: 'Activa un deposito (status=1). Solo si pertenece al idUser.'
    )]
    public function enableDeposit(int $idPorcent, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idPorcent, $idUser) {
            $this->debug('enableDeposit start', ['idPorcent' => $idPorcent, 'idUser' => $idUser]);

            $existing = $this->table('porcents')->where('id_porcent', $idPorcent)->first();
            if (!$existing) {
                return $this->validationError('El deposito no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El deposito no pertenece al usuario.');
            }

            $this->table('porcents')->where('id_porcent', $idPorcent)->update(['status' => 1]);

            $this->debug('enableDeposit enabled', ['id_porcent' => $idPorcent]);

            return $this->successResponse([
                'id_porcent' => $idPorcent,
                'status'     => 1,
            ], 'Deposito activado.');
        }, 'enable_deposit', [
            'idPorcent' => $idPorcent,
            'idUser'    => $idUser,
        ]);
    }
}