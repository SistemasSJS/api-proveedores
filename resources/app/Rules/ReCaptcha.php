<?php

namespace App\Rules;


use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class ReCaptcha implements Rule
{
    public function passes($attribute, $value)
    {
        $verifyOption = true;

        // Desactivar verificación SSL en entornos de desarrollo
        if (App::environment(['local', 'development'])) {
            $verifyOption = false;
        }

        $response = Http::asForm()
            ->withOptions(['verify' => $verifyOption])
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $value,
            ]);

        $result = $response->json();

        return isset($result['success']) && $result['success'] === true && $result['score'] >= 0.5;
    }

    public function message()
    {
        return 'La verificación de reCAPTCHA ha fallado. Intenta nuevamente.';
    }
}
