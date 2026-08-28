<?php

declare(strict_types=1);

namespace Tools\EgressMoney;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListOutflowsTool extends BaseTool
{
    #[McpTool(
        name: 'list_outflows',
        description: 'Lista los egresos del usuario con filtros, orden y paginacion. Parametros opcionales: idUser (default 1), idOutflowType, idCategory, idPorcent, description (LIKE), isInBudget (0/1), dateFrom, dateTo, sort (id_outflow|amount|set_date|description, default id_outflow), order (ASC|DESC, default DESC), page (default 1), length (10|25|50|100, default 50).'
    )]
    public function listOutflows(
        int $idUser = 1,
        ?int $idOutflowType = null,
        ?int $idCategory = null,
        ?int $idPorcent = null,
        ?string $description = null,
        ?int $isInBudget = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sort = 'id_outflow',
        string $order = 'DESC',
        int $page = 1,
        int $length = 50
    ): array {
        return $this->executeWithLogging(function () use ($idUser, $idOutflowType, $idCategory, $idPorcent, $description, $isInBudget, $dateFrom, $dateTo, $sort, $order, $page, $length) {
            $this->debug('listOutflows start', compact('idUser', 'idOutflowType', 'idCategory', 'idPorcent', 'description', 'isInBudget', 'dateFrom', 'dateTo', 'sort', 'order', 'page', 'length'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $length = in_array($length, [10, 25, 50, 100], true) ? $length : 50;
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $allowedSorts = ['id_outflow', 'amount', 'set_date', 'description'];
            $sort = in_array($sort, $allowedSorts, true) ? $sort : 'id_outflow';
            $offset = max(0, ($page - 1) * $length);

            $q = $this->table('outflows')->where('id_user', $idUser);
            if ($idOutflowType !== null) { $q->where('id_outflow_type', $idOutflowType); }
            if ($idCategory !== null)    { $q->where('id_category', $idCategory); }
            if ($idPorcent !== null)     { $q->where('id_porcent', $idPorcent); }
            if ($description !== null && $description !== '') { $q->where('description', 'LIKE', '%' . $description . '%'); }
            if ($isInBudget !== null)    { $q->where('is_in_budget', $isInBudget); }
            if ($dateFrom !== null && $dateFrom !== '')      { $q->where('set_date', '>=', $dateFrom); }
            if ($dateTo !== null && $dateTo !== '')          { $q->where('set_date', '<=', $dateTo); }

            $total = (int) $q->count();
            $rows = $q->orderBy($sort, $order)->offset($offset)->limit($length)->get()->map(function ($o) {
                return [
                    'id_outflow'      => (int) $o->id_outflow,
                    'id_outflow_type' => (int) $o->id_outflow_type,
                    'id_category'     => $o->id_category !== null ? (int) $o->id_category : null,
                    'id_porcent'      => (int) $o->id_porcent,
                    'amount'          => (float) $o->amount,
                    'description'     => $o->description,
                    'set_date'        => $o->set_date,
                    'status'          => (int) $o->status,
                    'is_in_budget'    => (int) $o->is_in_budget,
                    'create_at'       => $o->create_at,
                    'update_at'       => $o->update_at,
                ];
            })->all();

            $this->debug('listOutflows result', ['total' => $total, 'returned' => count($rows)]);

            return $this->successResponse([
                'items'       => $rows,
                'pagination'  => [
                    'current'      => $page,
                    'perPage'      => $length,
                    'total'        => $total,
                    'totalPages'   => $total > 0 ? (int) ceil($total / $length) : 1,
                ],
                'sort'        => $sort,
                'order'       => $order,
                'totalAmount' => (float) ($this->table('outflows')->where('id_user', $idUser)->sum('amount') ?? 0),
            ], 'Egresos listados.');
        }, 'list_outflows', compact('idUser', 'idOutflowType', 'idCategory', 'idPorcent', 'description', 'isInBudget', 'dateFrom', 'dateTo', 'sort', 'order', 'page', 'length'));
    }
}