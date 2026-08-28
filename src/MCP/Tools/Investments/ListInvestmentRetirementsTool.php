<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListInvestmentRetirementsTool extends BaseTool
{
    #[McpTool(
        name: 'list_investment_retirements',
        description: 'Lista los retiros de una inversion. Parametros: idInvestment (requerido), idUser (default 1). Solo si la inversion pertenece al usuario.'
    )]
    public function listInvestmentRetirements(int $idInvestment, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInvestment, $idUser) {
            $this->debug('listInvestmentRetirements start', compact('idInvestment', 'idUser'));

            $investment = $this->table('investments')->where('id_investment', $idInvestment)->first();
            if (!$investment) {
                return $this->validationError('La inversion no existe.');
            }
            $owner = $this->table('outflows')->where('id_outflow', $investment->id_outflow)->where('id_user', $idUser)->first();
            if (!$owner) {
                return $this->validationError('La inversion no pertenece al usuario.');
            }

            $rows = $this->table('retirement_investments')
                ->where('id_investment', $idInvestment)
                ->orderBy('init_date', 'DESC')
                ->get()
                ->map(fn($r) => [
                    'id_retirement_investment' => (int) $r->id_retirement_investment,
                    'id_investment'            => (int) $r->id_investment,
                    'id_user'                  => (int) $r->id_user,
                    'descripcion'              => $r->descripcion,
                    'retirement_amount'        => (float) $r->retirement_amount,
                    'init_date'                => $r->init_date,
                    'end_date'                 => $r->end_date,
                    'real_retribution'         => (float) $r->real_retribution,
                    'created_at'               => $r->created_at,
                ])->all();

            $this->debug('listInvestmentRetirements result', ['count' => count($rows)]);

            return $this->listResponse($rows, 'retirements');
        }, 'list_investment_retirements', compact('idInvestment', 'idUser'));
    }
}