<?php

namespace App\Traits;

use Illuminate\Foundation\Auth\User;

/**
 * Marca el item como visto (SP)
 * y sincroniza la notificación Laravel si existe.
 *
 * Convención:
 * -----------
 * - item_visto = false → pendiente
 * - item_visto = true  → visto
 *
 * La notificación es OPCIONAL.
 */
trait MarksAsNotified
{
  /**
   * Indica si el item ya fue visto.
   */
  public function isRead(): bool
  {
    return (bool) $this->item_visto;
  }

  /**
   * Asocia una notificación (opcional)
   * y deja el item como NO visto.
   *
   * @param int|string|null $notificationId ID de la notificación (Laravel usa UUID string)
   */
  public function addNotification(int|string|null $notificationId = null): void
  {
    $this->withoutEvents(function () use ($notificationId) {
      $this->item_visto = false;
      $this->notification_id = $notificationId;
      $this->save();
    });
  }

  /**
   * Marca el item como visto.
   * Si existe notificación, la marca como leída.
   *
   * @param User|null $user
   */
  public function markRead(?User $user = null): void
  {
    if ($this->isRead()) {
      return;
    }

    $this->withoutEvents(function () use ($user) {
      // 1️⃣ Marcar como visto (SIEMPRE)
      $this->item_visto = true;
      $this->save();
    });

    // 2️⃣ Marcar notificación Laravel como leída (SI EXISTE)
    if ($user && $this->notification_id) {
      $notification = $user->notifications()
        ->find($this->notification_id);

      if ($notification && is_null($notification->read_at)) {
        $notification->markAsRead();
      }
    }
  }
}


/**
 * 🔹 Con notificación
$notification = $user->notify(new \App\Notifications\...\AlgunaNotificacion::class());
$sp->addNotification($notification->id);

// luego...
$sp->markRead(auth()->user());

🔹 Sin notificación
$sp->addNotification(); // notification_id = null

// luego...
$sp->markRead(); // SOLO marca item_visto

🔹 Controller limpio
$solicitudPago->markRead(auth()->user());

 * 
 * 
 */
