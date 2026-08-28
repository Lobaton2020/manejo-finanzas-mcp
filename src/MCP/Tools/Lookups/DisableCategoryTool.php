<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class DisableCategoryTool extends BaseTool
{
    #[McpTool(
        name: 'disable_category',
        description: 'Desactiva una categoria (status=0). Solo si pertenece al idUser.'
    )]
    public function disableCategory(int $idCategory, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idCategory, $idUser) {
            $this->debug('disableCategory start', ['idCategory' => $idCategory, 'idUser' => $idUser]);

            $existing = $this->table('categories')->where('id_category', $idCategory)->first();
            if (!$existing) {
                return $this->validationError('La categoria no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('La categoria no pertenece al usuario.');
            }

            $this->table('categories')->where('id_category', $idCategory)->update(['status' => 0]);

            $this->debug('disableCategory disabled', ['id_category' => $idCategory]);

            return $this->successResponse([
                'id_category' => $idCategory,
                'status'      => 0,
            ], 'Categoria desactivada.');
        }, 'disable_category', [
            'idCategory' => $idCategory,
            'idUser'     => $idUser,
        ]);
    }
}