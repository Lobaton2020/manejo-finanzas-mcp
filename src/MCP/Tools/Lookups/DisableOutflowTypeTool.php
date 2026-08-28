<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class DisableOutflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'disable_outflow_type',
        description: 'Desactiva (soft disable) un tipo de egreso cambiando status=0. Solo si pertenece al idUser.'
    )]
    public function disableOutflowType(int $idOutflowType, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idOutflowType, $idUser) {
            $this->debug('disableOutflowType start', ['idOutflowType' => $idOutflowType, 'idUser' => $idUser]);

            $existing = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de egreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de egreso no pertenece al usuario.');
            }

            $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->update(['status' => 0]);

            $this->debug('disableOutflowType disabled', ['id_outflow_type' => $idOutflowType]);

            return $this->successResponse([
                'id_outflow_type' => $idOutflowType,
                'status'          => 0,
            ], 'Tipo de egreso desactivado.');
        }, 'disable_outflow_type', [
            'idOutflowType' => $idOutflowType,
            'idUser'        => $idUser,
        ]);
    }
}