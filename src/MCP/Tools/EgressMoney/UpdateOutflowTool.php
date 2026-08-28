<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateOutflowTool extends BaseTool
{
    #[McpTool(
        name: 'update_outflow',
        description: 'Actualiza campos editables de un egreso existente (amount, set_date, description, isInBudget, idCategory). No permite cambiar idOutflowType ni idPorcent (afectan balances). Solo si pertenece al idUser.'
    )]
    public function updateOutflow(
        int $idOutflow,
        int $idUser = 1,
        ?float $amount = null,
        ?string $setDate = null,
        ?string $description = null,
        ?bool $isInBudget = null,
        ?int $idCategory = null
    ): array {
        return $this->executeWithLogging(function () use ($idOutflow, $idUser, $amount, $setDate, $description, $isInBudget, $idCategory) {
            $this->debug('updateOutflow start', compact('idOutflow', 'idUser', 'amount', 'setDate', 'description', 'isInBudget', 'idCategory'));

            $row = $this->table('outflows')->where('id_outflow', $idOutflow)->where('id_user', $idUser)->first();
            if (!$row) {
                return $this->validationError('El egreso no existe o no pertenece al usuario.');
            }

            $data = [];
            if ($amount !== null) {
                if ($amount <= 0) {
                    return $this->validationError('El monto debe ser mayor a 0.');
                }
                $data['amount'] = $amount;
            }
            if ($setDate !== null && $setDate !== '')     { $data['set_date']    = $setDate; }
            if ($description !== null)                    { $data['description'] = $description; }
            if ($isInBudget !== null)                     { $data['is_in_budget'] = $isInBudget ? 1 : 0; }
            if ($idCategory !== null) {
                $cat = $this->table('categories')->where('id_category', $idCategory)->first();
                if (!$cat) {
                    return $this->validationError('La categoria no existe.');
                }
                $data['id_category'] = $idCategory;
            }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar.');
            }

            $data['update_at'] = date('Y-m-d H:i:s');
            $this->table('outflows')->where('id_outflow', $idOutflow)->update($data);
            $updated = $this->table('outflows')->where('id_outflow', $idOutflow)->first();

            $this->debug('updateOutflow updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_outflow'      => (int) $updated->id_outflow,
                'amount'          => (float) $updated->amount,
                'set_date'        => $updated->set_date,
                'description'     => $updated->description,
                'is_in_budget'    => (int) $updated->is_in_budget,
                'id_category'     => $updated->id_category !== null ? (int) $updated->id_category : null,
                'update_at'       => $updated->update_at,
            ], 'Egreso actualizado.');
        }, 'update_outflow', compact('idOutflow', 'idUser', 'amount', 'setDate', 'description', 'isInBudget', 'idCategory'));
    }
}