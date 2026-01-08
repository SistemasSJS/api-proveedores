<?php

namespace App\Traits;

/**
 * SE usa para mnarcar como visto el item y marcar la notificaicon leida.
 */
trait MarksAsNotified
{
  public function isRead(): bool
  {
    return $this->notificacion_id !== null;
  }

  public function markRead(int $notificationId): void
  {
    if ($this->isRead()) {
      return;
    }

    $this->notificacion_id = $notificationId;
    $this->save();
  }

  public function addNotification(int $notificationId): void
  {
    $this->markRead($notificationId);
  }
}
