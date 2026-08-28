<?php

declare(strict_types=1);

namespace Tools\Notes;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListNotesTool extends BaseTool
{
    #[McpTool(
        name: 'list_notes',
        description: 'Lista las notas del usuario. Parametros: idUser (default 1), includeInactive (default false). Devuelve id_note, description, total, status, create_at.'
    )]
    public function listNotes(int $idUser = 1, bool $includeInactive = false): array
    {
        return $this->executeWithLogging(function () use ($idUser, $includeInactive) {
            $this->debug('listNotes start', compact('idUser', 'includeInactive'));

            if (empty($this->requireUser($idUser))) {
                return $this->userNotFound();
            }

            $q = $this->table('notes')->where('id_user', $idUser);
            if (!$includeInactive) {
                $q->where('status', 1);
            }
            $rows = $q->orderBy('create_at', 'DESC')->get()->map(fn($n) => [
                'id_note'     => (int) $n->id_note,
                'description' => $n->description,
                'total'       => $n->total !== null ? (float) $n->total : 0.0,
                'status'      => $n->status !== null ? (int) $n->status : null,
                'create_at'   => $n->create_at,
            ])->all();

            $this->debug('listNotes result', ['count' => count($rows)]);

            return $this->listResponse($rows, 'notes');
        }, 'list_notes', compact('idUser', 'includeInactive'));
    }
}