<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListDepositsTool extends BaseTool
{
    #[McpTool(
        name: 'get_deposits',
        description: 'Lista los depositos/porcents del usuario. Parametros: idUser (default 1), includeInactive (default false). Devuelve id_porcent, name, status, create_at.'
    )]
    public function getDeposits(int $idUser = 1, bool $includeInactive = false): array
    {
        return $this->executeWithLogging(function () use ($idUser, $includeInactive) {
            $this->debug('getDeposits start', ['idUser' => $idUser, 'includeInactive' => $includeInactive]);

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $query = $this->table('porcents')->where('id_user', $idUser);
            if (!$includeInactive) {
                $query->where('status', 1);
            }
            $deposits = $query->orderBy('name', 'ASC')->get()->map(function ($d) {
                return [
                    'id_porcent' => (int) $d->id_porcent,
                    'id_user'    => (int) $d->id_user,
                    'name'       => $d->name,
                    'status'     => (int) $d->status,
                    'create_at'  => $d->create_at,
                ];
            })->all();

            $this->debug('getDeposits result', ['count' => count($deposits)]);

            return $this->listResponse($deposits, 'deposits');
        }, 'get_deposits', [
            'idUser'          => $idUser,
            'includeInactive' => $includeInactive,
        ]);
    }
}