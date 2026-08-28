<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetInflowTool extends BaseTool
{
    #[McpTool(
        name: 'get_inflow',
        description: 'Obtiene un ingreso por id con sus分配的 depositos (inflow_porcent). Solo si pertenece al idUser.'
    )]
    public function getInflow(int $idInflow, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInflow, $idUser) {
            $this->debug('getInflow start', compact('idInflow', 'idUser'));

            $row = $this->table('inflows')->where('id_inflow', $idInflow)->where('id_user', $idUser)->first();
            if (!$row) {
                return $this->validationError('El ingreso no existe o no pertenece al usuario.');
            }

            $distribucion = $this->table('inflow_porcent')
                ->where('id_inflow', $idInflow)
                ->get()
                ->map(fn($d) => [
                    'id_inflow_porcent' => (int) $d->id_inflow_porcent,
                    'id_porcent'        => (int) $d->id_porcent,
                    'porcent'           => (int) $d->porcent,
                    'status'            => (int) $d->status,
                ])->all();

            $this->debug('getInflow found', ['id_inflow' => $idInflow, 'distribucion' => count($distribucion)]);

            return $this->successResponse([
                'id_inflow'      => (int) $row->id_inflow,
                'id_inflow_type' => (int) $row->id_inflow_type,
                'total'          => (float) $row->total,
                'description'    => $row->description,
                'set_date'       => $row->set_date,
                'status'         => (int) $row->status,
                'create_at'      => $row->create_at,
                'update_at'      => $row->update_at,
                'distribution'   => $distribucion,
            ], 'Ingreso obtenido.');
        }, 'get_inflow', compact('idInflow', 'idUser'));
    }
}