<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Cambia según tus políticas de autorización
  }

  public function rules(): array
  {
    return [
      'title' => 'required|string|max:255',
      'message' => 'required|string',
      'type' => 'nullable|string|in:info,success,warning,error,danger',
      'data' => 'nullable|array'
    ];
  }
}
