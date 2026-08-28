<?php

declare(strict_types=1);

namespace Tools\Notes;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateNoteTool extends BaseTool
{
    #[McpTool(
        name: 'update_note',
        description: 'Actualiza descripcion y/o total de una nota. Solo si pertenece al idUser.'
    )]
    public function updateNote(int $idNote, int $idUser = 1, ?string $description = null, ?float $total = null): array
    {
        return $this->executeWithLogging(function () use ($idNote, $idUser, $description, $total) {
            $this->debug('updateNote start', compact('idNote', 'idUser', 'description', 'total'));

            $row = $this->table('notes')->where('id_note', $idNote)->first();
            if (!$row) {
                return $this->validationError('La nota no existe.');
            }
            if ((int) $row->id_user !== $idUser) {
                return $this->validationError('La nota no pertenece al usuario.');
            }

            $data = [];
            if ($description !== null && $description !== '') { $data['description'] = $description; }
            if ($total !== null) { $data['total'] = $total; }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar (description o total).');
            }

            $this->table('notes')->where('id_note', $idNote)->update($data);
            $updated = $this->table('notes')->where('id_note', $idNote)->first();

            $this->debug('updateNote updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_note'     => (int) $updated->id_note,
                'description' => $updated->description,
                'total'       => $updated->total !== null ? (float) $updated->total : 0.0,
                'status'      => $updated->status !== null ? (int) $updated->status : null,
            ], 'Nota actualizada.');
        }, 'update_note', compact('idNote', 'idUser', 'description', 'total'));
    }
}