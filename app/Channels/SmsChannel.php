<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Enviar SMS usando Twilio
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }
        
        $phone = $notifiable->phone ?? $notifiable->telefono;
        if (!$phone) {
            Log::warning('SMS Channel: Usuario sin teléfono', [
                'user_id' => $notifiable->id
            ]);
            return;
        }
        
        $message = $notification->toSms($notifiable);
        
        try {
            // Twilio API
            $response = Http::asForm()->withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->post("https://api.twilio.com/2010-04-01/Accounts/" . config('services.twilio.sid') . "/Messages.json", [
                'From' => config('services.twilio.from'),
                'To' => $this->formatPhoneNumber($phone),
                'Body' => $message
            ]);
            
            if ($response->successful()) {
                Log::info('SMS enviado correctamente', [
                    'user_id' => $notifiable->id,
                    'phone' => $phone
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error enviando SMS', [
                'error' => $e->getMessage(),
                'user_id' => $notifiable->id,
                'phone' => $phone
            ]);
        }
    }
    
    private function formatPhoneNumber(string $phone): string
    {
        // Formatear número mexicano
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 10) {
            return '+52' . $phone;
        }
        
        if (strlen($phone) === 12 && str_starts_with($phone, '52')) {
            return '+' . $phone;
        }
        
        return $phone;
    }
}