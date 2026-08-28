<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateInvestmentRetirementTool extends BaseTool
{
    #[McpTool(
        name: 'create_investment_retirement',
        description: 'Crea un retiro parcial de una inversion. Valida: retirementAmount <= inversion.amount, retirementAmount <= originalAmount - suma(retiros previos), realRetribution <= retirementAmount. Parametros requeridos: idInvestment, retirementAmount, initDate, endDate. Opcionales: idUser (default 1), realRetribution (default 0), descripcion.'
    )]
    public function createInvestmentRetirement(
        int $idInvestment,
        float $retirementAmount,
        string $initDate,
        string $endDate,
        int $idUser = 1,
        float $realRetribution = 0.0,
        ?string $descripcion = null
    ): array {
        return $this->executeWithLogging(function () use ($idInvestment, $idUser, $retirementAmount, $realRetribution, $initDate, $endDate, $descripcion) {
            $this->debug('createInvestmentRetirement start', compact('idInvestment', 'idUser', 'retirementAmount', 'realRetribution', 'initDate', 'endDate', 'descripcion'));

            $investment = $this->table('investments')->where('id_investment', $idInvestment)->first();
            if (!$investment) {
                return $this->validationError('La inversion no existe.');
            }

            $outflow = $this->table('outflows')->where('id_outflow', $investment->id_outflow)->first();
            if (!$outflow || (int) $outflow->id_user !== $idUser) {
                return $this->validationError('La inversion no pertenece al usuario.');
            }

            if ($retirementAmount <= 0) {
                return $this->validationError('El monto del retiro debe ser mayor a 0.');
            }

            if ($realRetribution > $retirementAmount) {
                return $this->validationError('La retribucion real no puede ser mayor al monto del retiro.');
            }

            $previousSum = (float) ($this->table('retirement_investments')->where('id_investment', $idInvestment)->sum('retirement_amount') ?? 0);
            $originalAmount = (float) $outflow->amount;
            $available = $originalAmount - $previousSum;

            if ($retirementAmount > $available) {
                return $this->validationError("El monto a retirar ({$retirementAmount}) excede el disponible ({$available}) considerando retiros anteriores.");
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('retirement_investments')->insertGetId([
                'id_user'           => $idUser,
                'id_investment'     => $idInvestment,
                'descripcion'       => $descripcion,
                'retirement_amount' => $retirementAmount,
                'init_date'         => $initDate,
                'end_date'          => $endDate,
                'real_retribution'  => $realRetribution,
                'created_at'        => $now,
            ]);

            $this->debug('createInvestmentRetirement inserted', ['id_retirement_investment' => $id]);

            return $this->successResponse([
                'id_retirement_investment' => (int) $id,
                'id_investment'            => $idInvestment,
                'retirement_amount'        => $retirementAmount,
                'real_retribution'         => $realRetribution,
                'init_date'                => $initDate,
                'end_date'                 => $endDate,
                'available_remaining'      => $available - $retirementAmount,
            ], 'Retiro registrado.');
        }, 'create_investment_retirement', compact('idInvestment', 'idUser', 'retirementAmount', 'realRetribution', 'initDate', 'endDate', 'descripcion'));
    }
}