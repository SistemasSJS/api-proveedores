<?php

namespace App\Support;

use App\Models\Proveedor;
use Illuminate\Support\Facades\Storage;

/**
 * Data URIs para imágenes en correos HTML (evita bloqueos por URL externas o rutas incorrectas).
 */
final class EmailLogoHelper
{
    public static function logoGestionProDataUri(): ?string
    {
        return self::fileToDataUri(public_path('assets/logos/logo-gestionpro.png'));
    }

    /**
     * Logo del proveedor desde disco (misma resolución de rutas que el PDF de presupuestos).
     */
    public static function proveedorDataUri(?Proveedor $proveedor): ?string
    {
        if (! $proveedor || empty($proveedor->logo)) {
            return null;
        }

        if (filter_var($proveedor->logo, FILTER_VALIDATE_URL)) {
            return null;
        }

        $logoPath = null;
        if (strpos($proveedor->logo, '/') === 0) {
            $logoPath = public_path($proveedor->logo);
        } elseif (strpos($proveedor->logo, 'storage/') === 0) {
            $logoPath = public_path($proveedor->logo);
        } else {
            if (Storage::disk('public')->exists($proveedor->logo)) {
                $logoPath = Storage::disk('public')->path($proveedor->logo);
            } else {
                $logoPath = public_path('storage/'.$proveedor->logo);
            }
        }

        return self::fileToDataUri($logoPath);
    }

    private static function fileToDataUri(?string $absolutePath): ?string
    {
        if (! $absolutePath || ! is_readable($absolutePath)) {
            return null;
        }

        $imageData = @file_get_contents($absolutePath);
        if ($imageData === false || $imageData === '') {
            return null;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($imageData);
    }
}
