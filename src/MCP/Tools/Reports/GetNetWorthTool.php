<?php

declare(strict_types=1);

namespace Tools\Reports;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class GetNetWorthTool extends BaseTool
{
    #[McpTool(
        name: 'get_net_worth',
        description: 'Calcula el patrimonio neto del usuario: suma de inflows (status=1) menos suma de outflows (status=1). Parametros: idUser (default 1).'
    )]
    public function getNetWorth(int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idUser) {
            $this->debug('getNetWorth start', compact('idUser'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $totalIncome = (float) ($this->table('inflows')->where('id_user', $idUser)->where('status', 1)->sum('total') ?? 0);
            $totalOutflow = (float) ($this->table('outflows')->where('id_user', $idUser)->where('status', 1)->sum('amount') ?? 0);
            $netWorth = $totalIncome - $totalOutflow;

            $this->debug('getNetWorth computed', ['income' => $totalIncome, 'outflow' => $totalOutflow, 'net' => $netWorth]);

            return $this->successResponse([
                'id_user'       => $idUser,
                'total_income'  => $totalIncome,
                'total_outflow' => $totalOutflow,
                'net_worth'     => $netWorth,
            ], 'Patrimonio neto.');
        }, 'get_net_worth', compact('idUser'));
    }
}