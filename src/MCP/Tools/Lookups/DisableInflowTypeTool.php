<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class DisableInflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'disable_inflow_type',
        description: 'Desactiva un tipo de ingreso (status=0). Solo si pertenece al idUser.'
    )]
    public function disableInflowType(int $idInflowType, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInflowType, $idUser) {
            $this->debug('disableInflowType start', ['idInflowType' => $idInflowType, 'idUser' => $idUser]);

            $existing = $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de ingreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de ingreso no pertenece al usuario.');
            }

            $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->update(['status' => 0]);

            $this->debug('disableInflowType disabled', ['id_inflow_type' => $idInflowType]);

            return $this->successResponse([
                'id_inflow_type' => $idInflowType,
                'status'         => 0,
            ], 'Tipo de ingreso desactivado.');
        }, 'disable_inflow_type', [
            'idInflowType' => $idInflowType,
            'idUser'       => $idUser,
        ]);
    }
}