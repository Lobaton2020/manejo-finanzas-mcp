<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetInvestmentTool extends BaseTool
{
    #[McpTool(
        name: 'get_investment',
        description: 'Obtiene una inversion por id desde investments_view. Solo si pertenece al idUser.'
    )]
    public function getInvestment(int $idInvestment, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInvestment, $idUser) {
            $this->debug('getInvestment start', compact('idInvestment', 'idUser'));

            $row = $this->table('investments_view')->where('id_investment', $idInvestment)->where('id_user', $idUser)->first();
            if (!$row) {
                return $this->validationError('La inversion no existe o no pertenece al usuario.');
            }

            $this->debug('getInvestment found', ['id_investment' => $idInvestment]);

            return $this->successResponse([
                'id_investment'              => (int) $row->id_investment,
                'id_outflow'                 => (int) $row->id_outflow,
                'state'                      => $row->state,
                'risk_level'                 => $row->risk_level,
                'init_date'                  => $row->init_date,
                'end_date'                   => $row->end_date,
                'real_retribution'           => (float) $row->real_retribution,
                'percent_annual_effective'   => (float) $row->percent_annual_effective,
                'id_group_investment'        => $row->id_group_investment !== null ? (int) $row->id_group_investment : null,
                'group_investment_name'      => $row->group_investment_name,
                'original_amount'            => (float) $row->original_amount,
                'amount'                     => (float) $row->amount,
                'earn_amount'                => $row->earn_amount !== null ? (float) $row->earn_amount : null,
                'earn_amount_all'            => $row->earn_amount_all !== null ? (float) $row->earn_amount_all : null,
                'description'                => $row->description,
            ], 'Inversion obtenida.');
        }, 'get_investment', compact('idInvestment', 'idUser'));
    }
}