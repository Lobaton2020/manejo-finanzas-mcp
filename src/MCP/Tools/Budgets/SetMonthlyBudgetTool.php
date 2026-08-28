<?php

declare(strict_types=1);

namespace Tools\Budgets;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class SetMonthlyBudgetTool extends BaseTool
{
    #[McpTool(
        name: 'set_monthly_budget',
        description: 'Crea o actualiza el budget mensual del usuario. Si ya existe un budget activo, lo actualiza; si no, lo crea. Parametros requeridos: total (> 0). Opcionales: idUser (default 1), description.'
    )]
    public function setMonthlyBudget(float $total, int $idUser = 1, ?string $description = null): array
    {
        return $this->executeWithLogging(function () use ($total, $idUser, $description) {
            $this->debug('setMonthlyBudget start', compact('idUser', 'total', 'description'));

            if ($total <= 0) {
                return $this->validationError('El total del budget debe ser mayor a 0.');
            }

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $now = date('Y-m-d H:i:s');

            $existing = $this->table('budget')->where('id_user', $idUser)->orderBy('created_at', 'DESC')->first();

            if ($existing) {
                $data = ['total' => $total, 'created_at' => $now];
                if ($description !== null) { $data['description'] = $description; }
                $this->table('budget')->where('id_budget', $existing->id_budget)->update($data);
                $id = (int) $existing->id_budget;
                $this->debug('setMonthlyBudget updated', ['id_budget' => $id]);
                return $this->successResponse([
                    'id_budget' => $id,
                    'id_user'   => $idUser,
                    'total'     => $total,
                    'action'    => 'updated',
                ], 'Budget actualizado.');
            }

            $id = $this->table('budget')->insertGetId([
                'id_user'     => $idUser,
                'total'       => $total,
                'description' => $description,
                'created_at'  => $now,
            ]);

            $this->debug('setMonthlyBudget created', ['id_budget' => $id]);

            return $this->successResponse([
                'id_budget' => (int) $id,
                'id_user'   => $idUser,
                'total'     => $total,
                'action'    => 'created',
            ], 'Budget creado.');
        }, 'set_monthly_budget', compact('idUser', 'total', 'description'));
    }
}