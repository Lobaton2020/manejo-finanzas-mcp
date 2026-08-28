<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class EnableOutflowTypeTool extends BaseTool
{
    #[McpTool(
        name: 'enable_outflow_type',
        description: 'Activa un tipo de egreso cambiando status=1. Solo si pertenece al idUser.'
    )]
    public function enableOutflowType(int $idOutflowType, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idOutflowType, $idUser) {
            $this->debug('enableOutflowType start', ['idOutflowType' => $idOutflowType, 'idUser' => $idUser]);

            $existing = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
            if (!$existing) {
                return $this->validationError('El tipo de egreso no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('El tipo de egreso no pertenece al usuario.');
            }

            $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->update(['status' => 1]);

            $this->debug('enableOutflowType enabled', ['id_outflow_type' => $idOutflowType]);

            return $this->successResponse([
                'id_outflow_type' => $idOutflowType,
                'status'          => 1,
            ], 'Tipo de egreso activado.');
        }, 'enable_outflow_type', [
            'idOutflowType' => $idOutflowType,
            'idUser'        => $idUser,
        ]);
    }
}