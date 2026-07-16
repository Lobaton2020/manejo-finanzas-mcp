<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class InflowMoneyTool extends BaseTool
{
    /**
     * Registra un nuevo ingreso (dinero que entra).
     * 
     * ¿Para qué sirve?: Registrar salarial, bonos, freelance, o cualquier dinero que recibas.
     * 
     * Validaciones:
     * 1. Usuario existe y está activo
     * 2. Tipo de ingreso existe y está activo
     * 3. Cada depósito existe, está activo y pertenece al usuario
     * 4. La suma de porcentajes debe ser exactamente 100
     * 
     * Importante: Los ingresos se distribuyen automáticamente entre depósitos según los porcentajes.
     * Ejemplo: Si recibes $1,000,000 y pones 70% a "cuenta principal" y 30% a "ahorros",
     *          se registran $700,000 en "cuenta principal" y $300,000 en "ahorros".
     * 
     * Flujo obligatorio:
     * 1. Llama get_inflow_types → obtener idInflowType válido
     * 2. Llama get_available_by_deposits → obtener idPorcent válido
     * 3. Llama inflow_money con los IDs obtenidos
     * 
     * @param int $idInflowType ID del tipo de ingreso (obtenido de get_inflow_types)
     * @param float $total Monto total del ingreso (debe ser > 0)
     * @param array $porcents Array de objetos {idPorcent: int, porcent: int}. La suma debe ser 100.
     *                         Ejemplo: [{"idPorcent": 1, "porcent": 70}, {"idPorcent": 2, "porcent": 30}]
     * @param string|null $setDate Fecha del ingreso (YYYY-MM-DD). Default: fecha actual
     * @param string $description Descripción del ingreso
     * @param int $idUser ID del usuario (default: 1)
     * @param bool $dryRun true = solo validar sin guardar, false = guardar en BD
     * @return array Resultado con success=true o errores de validación
     */
    #[McpTool(
        name: 'inflow_money',
        description: 'Crea un nuevo registro de ingreso. Valida: usuario activo, tipo de ingreso válido, depósitos válidos con porcentajes que sumen exactamente 100%. El ingreso se distribuye automáticamente según los porcentajes especificados. Parámetros requeridos: idInflowType, total, porcents (array con idPorcent y porcent), description. Opcionales: setDate, idUser, dryRun.'
    )]
    public function inflowMoney(
        int $idInflowType,
        float $total,
        array $porcents,
        ?string $setDate = null,
        string $description,
        int $idUser = 1,
        bool $dryRun = false
    ): array {
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
