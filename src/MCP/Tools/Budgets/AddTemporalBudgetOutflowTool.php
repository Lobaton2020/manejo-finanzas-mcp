<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class AddTemporalBudgetOutflowTool extends BaseTool
{
    #[McpTool(
        name: 'add_temporal_budget_outflow',
        description: 'Agrega un item de egreso a un presupuesto temporal. Valida que el budget y los depositos/categorias pertenezcan al idUser. Parametros requeridos: idTemporalBudget, idOutflowType, idCategory, idPorcent, amount, isInBudget. Opcionales: idUser (default 1), description.'
    )]
    public function addTemporalBudgetOutflow(
        int $idTemporalBudget,
        int $idOutflowType,
        int $idCategory,
        int $idPorcent,
        float $amount,
        bool $isInBudget,
        int $idUser = 1,
        ?string $description = null
    ): array {
        return $this->executeWithLogging(function () use ($idTemporalBudget, $idOutflowType, $idCategory, $idPorcent, $amount, $isInBudget, $idUser, $description) {
            $this->debug('addTemporalBudgetOutflow start', compact('idTemporalBudget', 'idOutflowType', 'idCategory', 'idPorcent', 'amount', 'isInBudget', 'idUser', 'description'));

            $tb = $this->table('temporal_budgets')->where('id_temporal_budget', $idTemporalBudget)->first();
            if (!$tb || (int) $tb->id_user !== $idUser) {
                return $this->validationError('El presupuesto temporal no existe o no pertenece al usuario.');
            }

            $ot = $this->table('outflowtypes')->where('id_outflow_type', $idOutflowType)->first();
            if (!$ot) { return $this->validationError('El tipo de egreso no existe.'); }

            $cat = $this->table('categories')->where('id_category', $idCategory)->where('id_outflow_type', $idOutflowType)->first();
            if (!$cat) { return $this->validationError('La categoria no existe o no pertenece al tipo de egreso.'); }

            $p = $this->table('porcents')->where('id_porcent', $idPorcent)->where('id_user', $idUser)->first();
            if (!$p) { return $this->validationError('El deposito no existe o no pertenece al usuario.'); }

            if ($amount <= 0) {
                return $this->validationError('El monto debe ser mayor a 0.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('temporal_budgets_outflow')->insertGetId([
                'id_user'            => $idUser,
                'id_temporal_budget' => $idTemporalBudget,
                'id_outflow_type'    => $idOutflowType,
                'id_category'        => $idCategory,
                'id_porcent'         => $idPorcent,
                'amount'             => $amount,
                'description'        => $description,
                'status'             => 1,
                'is_in_budget'       => $isInBudget ? 1 : 0,
                'update_at'          => $now,
                'create_at'          => $now,
            ]);

            $this->debug('addTemporalBudgetOutflow inserted', ['id_temporal_budget_outflow' => $id]);

            return $this->successResponse([
                'id_temporal_budget_outflow' => (int) $id,
                'id_temporal_budget'         => $idTemporalBudget,
                'amount'                     => $amount,
                'is_in_budget'               => $isInBudget ? 1 : 0,
                'status'                     => 1,
            ], 'Item agregado al presupuesto.');
        }, 'add_temporal_budget_outflow', compact('idTemporalBudget', 'idOutflowType', 'idCategory', 'idPorcent', 'amount', 'isInBudget', 'idUser', 'description'));
    }
}