<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class HideInvestmentTool extends BaseTool
{
    #[McpTool(
        name: 'hide_investment',
        description: 'Oculta una inversion (state=Ocultar). Solo si pertenece al idUser. La fila permanece en la tabla.'
    )]
    public function hideInvestment(int $idInvestment, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idInvestment, $idUser) {
            $this->debug('hideInvestment start', compact('idInvestment', 'idUser'));

            $row = $this->table('investments')->where('id_investment', $idInvestment)->first();
            if (!$row) {
                return $this->validationError('La inversion no existe.');
            }

            $ownerRow = $this->table('outflows')->where('id_outflow', $row->id_outflow)->where('id_user', $idUser)->first();
            if (!$ownerRow) {
                return $this->validationError('La inversion no pertenece al usuario.');
            }

            $now = date('Y-m-d H:i:s');
            $this->table('investments')->where('id_investment', $idInvestment)->update([
                'state'      => 'Ocultar',
                'updated_at' => $now,
            ]);

            $this->debug('hideInvestment hidden', ['id_investment' => $idInvestment]);

            return $this->successResponse([
                'id_investment' => $idInvestment,
                'state'         => 'Ocultar',
            ], 'Inversion ocultada.');
        }, 'hide_investment', compact('idInvestment', 'idUser'));
    }
}