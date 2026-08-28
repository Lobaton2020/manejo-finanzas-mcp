<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetOutflowTool extends BaseTool
{
    #[McpTool(
        name: 'get_outflow',
        description: 'Obtiene un egreso por id. Solo si pertenece al idUser.'
    )]
    public function getOutflow(int $idOutflow, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idOutflow, $idUser) {
            $this->debug('getOutflow start', compact('idOutflow', 'idUser'));

            $row = $this->table('outflows')->where('id_outflow', $idOutflow)->where('id_user', $idUser)->first();
            if (!$row) {
                return $this->validationError('El egreso no existe o no pertenece al usuario.');
            }

            $this->debug('getOutflow found', ['id_outflow' => $idOutflow]);

            return $this->successResponse([
                'id_outflow'      => (int) $row->id_outflow,
                'id_outflow_type' => (int) $row->id_outflow_type,
                'id_category'     => $row->id_category !== null ? (int) $row->id_category : null,
                'id_porcent'      => (int) $row->id_porcent,
                'amount'          => (float) $row->amount,
                'description'     => $row->description,
                'set_date'        => $row->set_date,
                'status'          => (int) $row->status,
                'is_in_budget'    => (int) $row->is_in_budget,
                'create_at'       => $row->create_at,
                'update_at'       => $row->update_at,
            ], 'Egreso obtenido.');
        }, 'get_outflow', compact('idOutflow', 'idUser'));
    }
}