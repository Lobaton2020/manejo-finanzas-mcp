<?php

declare(strict_types=1);

namespace Tools\Reports;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetNetWorthWithLoansTool extends BaseTool
{
    #[McpTool(
        name: 'get_net_worth_with_loans',
        description: 'Calcula el patrimonio neto del usuario restando los prestamos FROM_ME activos (salidas). Equivale a getNetWorth menos suma(moneyloans FROM_ME status activo). Parametros: idUser (default 1).'
    )]
    public function getNetWorthWithLoans(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('getNetWorthWithLoans start', compact('idUser'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $totalIncome = (float) ($this->table('inflows')->where('id_user', $idUser)->where('status', 1)->sum('total') ?? 0);
            $totalOutflow = (float) ($this->table('outflows')->where('id_user', $idUser)->where('status', 1)->sum('amount') ?? 0);
            $loansFromMe = (float) ($this->table('moneyloans')->where('id_user', $idUser)->where('type', 'FROM_ME')->whereNotNull('status')->sum('total') ?? 0);

            $netWorth = $totalIncome - $totalOutflow - $loansFromMe;

            $this->debug('getNetWorthWithLoans computed', ['income' => $totalIncome, 'outflow' => $totalOutflow, 'loans' => $loansFromMe, 'net' => $netWorth]);

            return $this->successResponse([
                'id_user'         => $idUser,
                'total_income'    => $totalIncome,
                'total_outflow'   => $totalOutflow,
                'loans_from_me'   => $loansFromMe,
                'net_worth'       => $netWorth,
            ], 'Patrimonio neto con prestamos.');
        }, 'get_net_worth_with_loans', compact('idUser'));
    }
}