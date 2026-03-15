<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class InflowMoneyTool extends BaseTool
{
    /**
     * Create a new inflow (income) record.
     * Validates: User exists and is active, Inflow type exists and is active,
     * Each deposit exists/is active/belongs to user, Sum of percentages = 100.
     * Parameters:
     *   - idInflowType (required): Inflow type ID
     *   - total (required): Total amount of the income (> 0)
     *   - porcents (required): Array of {idPorcent: number, porcent: number} (sum must = 100)
     *   - setDate (optional): Date of inflow (default: current date)
     *   - description (optional): Additional description
     *   - idUser (optional): User ID (default: 1)
     *   - dryRun (optional): If true, validates but does not persist (default: false)
     * Returns success with inflow details or validation errors.
     */
    #[McpTool(name: 'inflow_money')]
    public function inflowMoney(
        int $idInflowType,
        float $total,
        array $porcents,
        ?string $setDate = null,
        ?string $description = null,
        int $idUser = 1,
        bool $dryRun = false
    ): array {
        date_default_timezone_set('America/Bogota');
        return $this->executeWithLogging(function () use ($idInflowType, $total, $porcents, $setDate, $description, $idUser, $dryRun) {
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

            $inflowType = $this->table('inflowtypes')
                ->where('id_inflow_type', $idInflowType)
                ->where('status', 1)
                ->first();

            if (!$inflowType) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El tipo de ingreso no existe o está inactivo.'
                    ]
                ];
            }

            if ($total <= 0) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: El monto total debe ser mayor a 0.'
                    ]
                ];
            }

            if (empty($porcents)) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => 'Error: Debe especificar al menos un depósito con su porcentaje.'
                    ]
                ];
            }

            $sumPorcent = 0;
            $deposits = [];
            foreach ($porcents as $index => $item) {
                if (!isset($item['idPorcent']) || !isset($item['porcent'])) {
                    return [
                        'content' => [
                            'type' => 'text',
                            'text' => "Error: Cada elemento de porcents debe tener 'idPorcent' y 'porcent'. Error en índice $index."
                        ]
                    ];
                }

                $deposit = $this->table('porcents')
                    ->where('id_porcent', $item['idPorcent'])
                    ->where('id_user', $idUser)
                    ->where('status', 1)
                    ->first();

                if (!$deposit) {
                    return [
                        'content' => [
                            'type' => 'text',
                            'text' => "Error: El depósito con ID {$item['idPorcent']} no existe, está inactivo o no pertenece al usuario."
                        ]
                    ];
                }

                $sumPorcent += $item['porcent'];
                $deposits[] = [
                    'idPorcent' => $item['idPorcent'],
                    'porcent' => $item['porcent'],
                    'depositName' => $deposit->name
                ];
            }

            if ($sumPorcent !== 100) {
                return [
                    'content' => [
                        'type' => 'text',
                        'text' => "Error: La suma de los porcentajes debe ser igual a 100. Suma actual: $sumPorcent"
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
                            'message' => 'Validación exitosa. El ingreso se puede crear.',
                            'inflow' => [
                                'id_inflow_type' => $idInflowType,
                                'id_user' => $idUser,
                                'total' => $total,
                                'description' => $description ?? '',
                                'set_date' => $setDate,
                                'deposits' => $deposits,
                            ],
                        ], JSON_PRETTY_PRINT)
                    ]
                ];
            }

            $inflowId = $this->transaction(function() use ($idInflowType, $idUser, $total, $porcents, $setDate, $description) {
                $id = $this->table('inflows')->insertGetId([
                    'id_inflow_type' => $idInflowType,
                    'id_user' => $idUser,
                    'total' => $total,
                    'description' => $description ?? '',
                    'set_date' => $setDate,
                    'status' => 1,
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                ]);

                foreach ($porcents as $item) {
                    $this->table('inflow_porcent')->insert([
                        'id_inflow' => $id,
                        'id_porcent' => $item['idPorcent'],
                        'porcent' => $item['porcent'],
                        'status' => 1,
                        'create_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                return $id;
            });

            return [
                'content' => [
                    'type' => 'text',
                    'text' => json_encode([
                        'success' => true,
                        'message' => 'Ingreso creado exitosamente.',
                        'inflow' => [
                            'id' => $inflowId,
                            'total' => $total,
                            'date' => $setDate,
                            'type' => $inflowType->name,
                            'deposits' => $deposits,
                        ],
                    ], JSON_PRETTY_PRINT)
                ]
            ];
        }, 'inflow_money', [
            'idInflowType' => $idInflowType,
            'total' => $total,
            'porcents' => $porcents,
            'setDate' => $setDate,
            'description' => $description,
            'idUser' => $idUser,
            'dryRun' => $dryRun
        ]);
    }
}
