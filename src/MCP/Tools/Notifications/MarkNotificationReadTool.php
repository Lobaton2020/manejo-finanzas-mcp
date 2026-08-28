<?php

declare(strict_types=1);

namespace Tools\Notifications;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class MarkNotificationReadTool extends BaseTool
{
    #[McpTool(
        name: 'mark_notification_read',
        description: 'Marca una notificacion como leida (readed=1). Solo si pertenece al idUser.'
    )]
    public function markNotificationRead(int $idNotification, int $idUser = 1): array
    {
        return $this->executeWithLogging(function () use ($idNotification, $idUser) {
            $this->debug('markNotificationRead start', compact('idNotification', 'idUser'));

            $row = $this->table('notifications')->where('id_notification', $idNotification)->first();
            if (!$row) {
                return $this->validationError('La notificacion no existe.');
            }
            if ((int) $row->id_user !== $idUser) {
                return $this->validationError('La notificacion no pertenece al usuario.');
            }

            $this->table('notifications')->where('id_notification', $idNotification)->update(['readed' => 1]);

            $this->debug('markNotificationRead marked', ['id_notification' => $idNotification]);

            return $this->successResponse([
                'id_notification' => $idNotification,
                'readed'          => 1,
            ], 'Notificacion marcada como leida.');
        }, 'mark_notification_read', compact('idNotification', 'idUser'));
    }
}