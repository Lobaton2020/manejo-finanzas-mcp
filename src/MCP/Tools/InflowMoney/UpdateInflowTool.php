<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateInflowTool extends BaseTool
{
    #[McpTool(
        name: 'update_inflow',
        description: 'Actualiza campos editables de un ingreso existente (total, setDate, description, idInflowType). No permite cambiar distribucion de depositos (afecta balances). Solo si pertenece al idUser.'
    )]
    public function updateInflow(
        int $idInflow,
        int $idUser = 1,
        ?float $total = null,
        ?string $setDate = null,
        ?string $description = null,
        ?int $idInflowType = null
    ): array {
        return $this->executeWithLogging(function () use ($idInflow, $idUser, $total, $setDate, $description, $idInflowType) {
            $this->debug('updateInflow start', compact('idInflow', 'idUser', 'total', 'setDate', 'description', 'idInflowType'));

            $row = $this->table('inflows')->where('id_inflow', $idInflow)->where('id_user', $idUser)->first();
            if (!$row) {
                return $this->validationError('El ingreso no existe o no pertenece al usuario.');
            }

            $data = [];
            if ($total !== null) {
                if ($total <= 0) {
                    return $this->validationError('El total debe ser mayor a 0.');
                }
                $data['total'] = $total;
            }
            if ($setDate !== null && $setDate !== '') { $data['set_date']    = $setDate; }
            if ($description !== null)                { $data['description'] = $description; }
            if ($idInflowType !== null) {
                $it = $this->table('inflowtypes')->where('id_inflow_type', $idInflowType)->first();
                if (!$it) {
                    return $this->validationError('El tipo de ingreso no existe.');
                }
                $data['id_inflow_type'] = $idInflowType;
            }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar.');
            }

            $data['update_at'] = date('Y-m-d H:i:s');
            $this->table('inflows')->where('id_inflow', $idInflow)->update($data);
            $updated = $this->table('inflows')->where('id_inflow', $idInflow)->first();

            $this->debug('updateInflow updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_inflow'      => (int) $updated->id_inflow,
                'id_inflow_type' => (int) $updated->id_inflow_type,
                'total'          => (float) $updated->total,
                'description'    => $updated->description,
                'set_date'       => $updated->set_date,
                'update_at'      => $updated->update_at,
            ], 'Ingreso actualizado.');
        }, 'update_inflow', compact('idInflow', 'idUser', 'total', 'setDate', 'description', 'idInflowType'));
    }
}