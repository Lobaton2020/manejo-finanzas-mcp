<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class EnableInflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'enable_inflow_type',
        description: 'Activa un tipo de ingreso (status=1). Solo si pertenece al idUser.'
    )]
    public function enableInflowType(int $idInflowType, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInflowType, $idUser) {
            $this->debug('enableInflowType start', ['idInflowType' => $idInflowType, 'idUser' => $idUser]);

            $existing = $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de ingreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de ingreso no pertenece al usuario.');
            }

            $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->update(['status' => 1]);

            $this->debug('enableInflowType enabled', ['id_inflow_type' => $idInflowType]);

            return $this->successResponse([
                'id_inflow_type' => $idInflowType,
                'status'         => 1,
            ], 'Tipo de ingreso activado.');
        }, 'enable_inflow_type', [
            'idInflowType' => $idInflowType,
            'idUser'       => $idUser,
        ]);
    }
}