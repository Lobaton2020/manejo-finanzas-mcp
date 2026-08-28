<?php

declare(strict_types=1);

namespace Tools\Notes;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class DisableNoteTool extends BaseTool
{
    #[McpTool(
        name: 'disable_note',
        description: 'Desactiva una nota (status=0). Solo si pertenece al idUser.'
    )]
    public function disableNote(int $idNote, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idNote, $idUser) {
            $this->debug('disableNote start', compact('idNote', 'idUser'));

            $row = $this->table('notes')->where('id_note', $idNote)->first();
            if (!$row) {
                return $this->validationError('La nota no existe.');
            }
            if ((int) $row->id_user !== $idUser) {
                return $this->validationError('La nota no pertenece al usuario.');
            }

            $this->table('notes')->where('id_note', $idNote)->update(['status' => 0]);

            $this->debug('disableNote disabled', ['id_note' => $idNote]);

            return $this->successResponse([
                'id_note' => $idNote,
                'status'  => 0,
            ], 'Nota desactivada.');
        }, 'disable_note', compact('idNote', 'idUser'));
    }
}