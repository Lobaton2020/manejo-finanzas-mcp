<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class OutflowMoneyTool extends BaseTool
{
    /**
     * Create a new outflow (expense) record.
     * Validates: User exists and is active, Outflow type exists and is active,
     * Category exists/is active/belongs to outflow type, Deposit exists/is active/belongs to user,
     * Amount > 0, Amount <= available_balance.
     * If outflow type name contains "inversion" (case-insensitive), automatically creates an investment.
     * Parameters:
     *   - idOutflowType (required): Outflow type ID
     *   - idCategory (required): Category ID
     *   - idPorcent (required): Deposit/percentage source ID
     *   - amount (required): Amount to withdraw (> 0)
     *   - setDate (optional): Date of outflow (default: current date)
     *   - isInBudget (optional): Whether it's part of a budget (default: true)
     *   - description (optional): Additional description
     *   - idUser (optional): User ID (default: 1)
     *   - dryRun (optional): If true, validates but does not persist (default: false)
     * Returns success with outflow details or validation errors.
     */
    #[McpTool(name: 'outflow_money')]
    public function outflowMoney(
        int $idOutflowType,
        int $idCategory,
        int $idPorcent,
        float $amount,
        ?string $setDate = null,
        ?bool $isInBudget = true,
        ?string $description = null,
        int $idUser = 1,
        bool $dryRun = false
    ): array {
        return $this->executeWithLogging(function () use ($idOutflowType, $idCategory, $idPorcent, $amount, $setDate, $isInBudget, $description, $idUser, $dryRun) {
            $setDate = $setDate ?? date('Y-m-d');

            $user = $this->table('users')
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->first();

            if (!$user) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El usuario no existe o está inactivo.'
                    ]
                ];
            }

            $outflowType = $this->table('outflowtypes')
                ->where('id_outflow_type', $idOutflowType)
                ->where('status', 1)
                ->first();

            if (!$outflowType) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El tipo de egreso no existe o está inactivo.'
                    ]
                ];
            }

            $category = $this->table('categories')
                ->where('id_category', $idCategory)
                ->where('id_outflow_type', $idOutflowType)
                ->where('status', 1)
                ->first();

            if (!$category) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: La categoría no existe, está inactiva o no pertenece al tipo de egreso seleccionado.'
                    ]
                ];
            }

            $deposit = $this->table('porcents')
                ->where('id_porcent', $idPorcent)
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->first();

            if (!$deposit) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El depósito no existe, está inactivo o no pertenece al usuario.'
                    ]
                ];
            }

            if ($amount <= 0) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El monto debe ser mayor a 0.'
                    ]
                ];
            }

            $balanceData = $this->table('porcents')
                ->selectRaw('
                    (SELECT COALESCE(SUM(i.total * (ip.porcent / 100)), 0)
                     FROM inflow_porcent ip
                     INNER JOIN inflows i ON i.id_inflow = ip.id_inflow
                     WHERE ip.id_porcent = porcents.id_porcent
                     AND i.id_user = ?
                     AND i.status = 1) as total_income,
                    (SELECT COALESCE(SUM(o.amount), 0)
                     FROM outflows o
                     WHERE o.id_porcent = porcents.id_porcent
                     AND o.id_user = ?
                     AND o.status = 1) as total_outflow
                ', [$idUser, $idUser])
                ->where('id_porcent', $idPorcent)
                ->where('id_user', $idUser)
                ->where('status', 1)
                ->first();

            $availableBalance = (float) ($balanceData->total_income ?? 0) - (float) ($balanceData->total_outflow ?? 0);

            if ($amount > $availableBalance) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => "Error: El balance disponible ($availableBalance) NO es suficiente para el monto solicitado ($amount)."
                    ]
                ];
            }

            if ($dryRun) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => json_encode([
                            'valid' => true,
                            'dry_run' => true,
                            'message' => 'Validación exitosa. El egreso se puede crear.',
                            'outflow' => [
                                'id_outflow_type' => $idOutflowType,
                                'id_category' => $idCategory,
                                'id_porcent' => $idPorcent,
                                'id_user' => $idUser,
                                'amount' => $amount,
                                'description' => $description ?? '',
                                'set_date' => $setDate,
                                'is_in_budget' => $isInBudget ? 1 : 0,
                            ],
                            'investment_will_be_created' => stripos($outflowType->name, 'inversion') !== false,
                        ], JSON_PRETTY_PRINT)
                    ]
                ];
            }

            $outflowId = $this->table('outflows')->insertGetId([
                'id_outflow_type' => $idOutflowType,
                'id_category' => $idCategory,
                'id_porcent' => $idPorcent,
                'id_user' => $idUser,
                'amount' => $amount,
                'description' => $description ?? '',
                'set_date' => $setDate,
                'is_in_budget' => $isInBudget ? 1 : 0,
                'status' => 1,
                'create_at' => date('Y-m-d H:i:s'),
                'update_at' => date('Y-m-d H:i:s'),
            ]);

            $investmentCreated = false;
            if (stripos($outflowType->name, 'inversion') !== false) {
                $this->table('investments')->insert([
                    'id_outflow' => $outflowId,
                    'init_date' => $setDate,
                    'amount' => $amount,
                    'state' => 'activa',
                ]);
                $investmentCreated = true;
            }

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode([
                        'success' => true,
                        'message' => 'Egreso creado exitosamente.',
                        'outflow' => [
                            'id' => $outflowId,
                            'amount' => $amount,
                            'date' => $setDate,
                            'type' => $outflowType->name,
                            'category' => $category->name,
                            'deposit' => $deposit->name,
                        ],
                        'investment_created' => $investmentCreated,
                    ], JSON_PRETTY_PRINT)
                ]
            ];
        }, 'outflow_money', [
            'idOutflowType' => $idOutflowType,
            'idCategory' => $idCategory,
            'idPorcent' => $idPorcent,
            'amount' => $amount,
            'setDate' => $setDate,
            'isInBudget' => $isInBudget,
            'description' => $description,
            'idUser' => $idUser,
            'dryRun' => $dryRun
        ]);
    }
}
