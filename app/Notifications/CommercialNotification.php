<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommercialNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
        private readonly ?string $url = null,
        private readonly ?string $icon = null,
        private readonly string $severity = 'info',
        private readonly ?string $entityType = null,
        private readonly ?int $entityId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url,
            'icon' => $this->icon,
            'severity' => $this->severity,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ];
    }
}
