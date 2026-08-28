<?php

declare(strict_types=1);

namespace Tools\Notifications;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class ListNotificationsTool extends BaseTool
{
    #[McpTool(
        name: 'list_notifications',
        description: 'Lista las notificaciones del usuario. Parametros: idUser (default 1), onlyUnread (default false), limit (default 50).'
    )]
    public function listNotifications(int $idUser = 1, bool $onlyUnread = false, int $limit = 50): array
    {
        return $this->executeWithLogging(function () use ($idUser, $onlyUnread, $limit) {
            $this->debug('listNotifications start', compact('idUser', 'onlyUnread', 'limit'));

            $q = $this->table('notifications')->where('id_user', $idUser);
            if ($onlyUnread) {
                $q->where('readed', 0);
            }
            $rows = $q->orderBy('create_at', 'DESC')->limit(max(1, min($limit, 500)))->get()->map(fn($n) => [
                'id_notification'        => (int) $n->id_notification,
                'id_user'                => (int) $n->id_user,
                'key_notification_type'  => $n->key_notification_type,
                'readed'                 => (int) $n->readed,
                'create_at'              => $n->create_at,
            ])->all();

            $this->debug('listNotifications result', ['count' => count($rows)]);

            return $this->listResponse($rows, 'notifications');
        }, 'list_notifications', compact('idUser', 'onlyUnread', 'limit'));
    }
}