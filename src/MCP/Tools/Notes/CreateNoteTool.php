<?php

declare(strict_types=1);

namespace Tools\Notes;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class CreateNoteTool extends BaseTool
{
    #[McpTool(
        name: 'create_note',
        description: 'Crea una nota financiera. Parametros requeridos: description, total. Opcionales: idUser (default 1), status (default 1).'
    )]
    public function createNote(string $description, float $total, int $idUser = 1, int $status = 1): array
    {
        return $this->executeWithLogging(function () use ($description, $total, $idUser, $status) {
            $this->debug('createNote start', compact('idUser', 'description', 'total', 'status'));

            if (empty(trim($description))) {
                return $this->validationError('La descripcion de la nota es requerida.');
            }

            $now = date('Y-m-d H:i:s');
            $id = $this->table('notes')->insertGetId([
                'id_user'     => $idUser,
                'description' => $description,
                'total'       => $total,
                'status'      => $status,
                'create_at'   => $now,
            ]);

            $this->debug('createNote inserted', ['id_note' => $id]);

            return $this->successResponse([
                'id_note'     => (int) $id,
                'description' => $description,
                'total'       => $total,
                'status'      => $status,
                'create_at'   => $now,
            ], 'Nota creada.');
        }, 'create_note', compact('description', 'total', 'idUser', 'status'));
    }
}