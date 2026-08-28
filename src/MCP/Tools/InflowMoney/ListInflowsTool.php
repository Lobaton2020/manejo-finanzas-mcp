<?php

declare(strict_types=1);

namespace Tools\InflowMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListInflowsTool extends BaseTool
{
    #[McpTool(
        name: 'list_inflows',
        description: 'Lista los ingresos del usuario con filtros, orden y paginacion. Parametros opcionales: idUser (default 1), idInflowType, description (LIKE), dateFrom, dateTo, sort (id_inflow|total|set_date, default id_inflow), order (ASC|DESC, default DESC), page (default 1), length (10|25|50|100, default 50).'
    )]
    public function listInflows(
        int $idUser = 1,
        ?int $idInflowType = null,
        ?string $description = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sort = 'id_inflow',
        string $order = 'DESC',
        int $page = 1,
        int $length = 50
    ): array {
        return $this->executeWithLogging(function () use ($idUser, $idInflowType, $description, $dateFrom, $dateTo, $sort, $order, $page, $length) {
            $this->debug('listInflows start', compact('idUser', 'idInflowType', 'description', 'dateFrom', 'dateTo', 'sort', 'order', 'page', 'length'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $length = in_array($length, [10, 25, 50, 100], true) ? $length : 50;
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $allowedSorts = ['id_inflow', 'total', 'set_date'];
            $sort = in_array($sort, $allowedSorts, true) ? $sort : 'id_inflow';
            $offset = max(0, ($page - 1) * $length);

            $q = $this->table('inflows')->where('id_user', $idUser);
            if ($idInflowType !== null) { $q->where('id_inflow_type', $idInflowType); }
            if ($description !== null && $description !== '') { $q->where('description', 'LIKE', '%' . $description . '%'); }
            if ($dateFrom !== null && $dateFrom !== '')      { $q->where('set_date', '>=', $dateFrom); }
            if ($dateTo !== null && $dateTo !== '')          { $q->where('set_date', '<=', $dateTo); }

            $total = (int) $q->count();
            $rows = $q->orderBy($sort, $order)->offset($offset)->limit($length)->get()->map(function ($i) {
                return [
                    'id_inflow'      => (int) $i->id_inflow,
                    'id_inflow_type' => (int) $i->id_inflow_type,
                    'total'          => (float) $i->total,
                    'description'    => $i->description,
                    'set_date'       => $i->set_date,
                    'status'         => (int) $i->status,
                    'create_at'      => $i->create_at,
                    'update_at'      => $i->update_at,
                ];
            })->all();

            $this->debug('listInflows result', ['total' => $total, 'returned' => count($rows)]);

            return $this->successResponse([
                'items'      => $rows,
                'pagination' => [
                    'current'    => $page,
                    'perPage'    => $length,
                    'total'      => $total,
                    'totalPages' => $total > 0 ? (int) ceil($total / $length) : 1,
                ],
                'sort'       => $sort,
                'order'      => $order,
                'totalAmount'=> (float) ($this->table('inflows')->where('id_user', $idUser)->sum('total') ?? 0),
            ], 'Ingresos listados.');
        }, 'list_inflows', compact('idUser', 'idInflowType', 'description', 'dateFrom', 'dateTo', 'sort', 'order', 'page', 'length'));
    }
}