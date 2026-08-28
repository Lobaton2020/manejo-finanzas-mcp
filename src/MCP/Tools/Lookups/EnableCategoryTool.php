<?php

declare(strict_types=1);

namespace Tools\Lookups;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class EnableCategoryTool extends BaseTool
{
    #[McpTool(
        name: 'enable_category',
        description: 'Activa una categoria (status=1). Solo si pertenece al idUser.'
    )]
    public function enableCategory(int $idCategory, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idCategory, $idUser) {
            $this->debug('enableCategory start', ['idCategory' => $idCategory, 'idUser' => $idUser]);

            $existing = $this->table('categories')->where('id_category', $idCategory)->first();
            if (!$existing) {
                return $this->validationError('La categoria no existe.');
            }
            if ((int) $existing->id_user !== $idUser) {
                return $this->validationError('La categoria no pertenece al usuario.');
            }

            $this->table('categories')->where('id_category', $idCategory)->update(['status' => 1]);

            $this->debug('enableCategory enabled', ['id_category' => $idCategory]);

            return $this->successResponse([
                'id_category' => $idCategory,
                'status'      => 1,
            ], 'Categoria activada.');
        }, 'enable_category', [
            'idCategory' => $idCategory,
            'idUser'     => $idUser,
        ]);
    }
}