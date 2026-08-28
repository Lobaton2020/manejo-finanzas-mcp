<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListInvestmentsTool extends BaseTool
{
    #[McpTool(
        name: 'list_investments',
        description: 'Lista las inversiones del usuario usando investments_view. Excluye HIDDED/COMPLETED/LOST por defecto. Parametros: idUser (default 1), includeHidden (default false), state (opcional: Creado|Activo|Expirado|Cancelado|Completado|Perdido), idGroupInvestment (opcional).'
    )]
    public function listInvestments(
        int $idUser = 1,
        bool $includeHidden = false,
        ?string $state = null,
        ?int $idGroupInvestment = null
    ): array {
        return $this->executeWithLogging(function () use ($idUser, $includeHidden, $state, $idGroupInvestment) {
            $this->debug('listInvestments start', compact('idUser', 'includeHidden', 'state', 'idGroupInvestment'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $q = $this->table('investments_view')->where('id_user', $idUser);

            if ($state !== null && $state !== '') {
                $q->where('state', $state);
            } elseif (!$includeHidden) {
                $q->whereNotIn('state', ['Ocultar', 'Completado', 'Perdido']);
            }

            if ($idGroupInvestment !== null) {
                $q->where('id_group_investment', $idGroupInvestment);
            }

            $rows = $q->orderBy('init_date', 'DESC')->get()->map(function ($r) {
                return [
                    'id_investment'              => (int) $r->id_investment,
                    'id_outflow'                 => (int) $r->id_outflow,
                    'state'                      => $r->state,
                    'risk_level'                 => $r->risk_level,
                    'init_date'                  => $r->init_date,
                    'end_date'                   => $r->end_date,
                    'real_retribution'           => (float) $r->real_retribution,
                    'percent_annual_effective'   => (float) $r->percent_annual_effective,
                    'id_group_investment'        => $r->id_group_investment !== null ? (int) $r->id_group_investment : null,
                    'group_investment_name'      => $r->group_investment_name,
                    'original_amount'            => (float) $r->original_amount,
                    'amount'                     => (float) $r->amount,
                    'earn_amount'                => $r->earn_amount !== null ? (float) $r->earn_amount : null,
                    'earn_amount_all'            => $r->earn_amount_all !== null ? (float) $r->earn_amount_all : null,
                    'description'                => $r->description,
                ];
            })->all();

            $this->debug('listInvestments result', ['count' => count($rows)]);

            return $this->successResponse([
                'items' => $rows,
                'count' => count($rows),
            ], 'Inversiones listadas.');
        }, 'list_investments', compact('idUser', 'includeHidden', 'state', 'idGroupInvestment'));
    }
}