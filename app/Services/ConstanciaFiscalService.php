<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConstanciaFiscalService
{
    /**
     * Extrae los datos fiscales de una constancia fiscal PDF.
     * Lee el QR del PDF y obtiene la información del SAT.
     *
     * @param string $pdfPath Ruta completa al archivo PDF
     * @return array|null Array con los datos fiscales o null si falla
     */
    public function extraerDatosFiscales(string $pdfPath): ?array
    {
        try {
            // 1. Extraer QR del PDF
            $qrUrl = $this->extraerQRdePDF($pdfPath);
            
            if (!$qrUrl) {
                Log::warning('No se pudo extraer el código QR del PDF');
                return null;
            }

            // 2. Obtener datos del SAT desde la URL del QR
            $datosFiscales = $this->obtenerDatosDelSAT($qrUrl);

            return $datosFiscales;
        } catch (\Exception $e) {
            Log::error('Error al extraer datos fiscales: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrae la URL del código QR del PDF.
     * 
     * @param string $pdfPath
     * @return string|null
     */
    private function extraerQRdePDF(string $pdfPath): ?string
    {
        try {
            // Convertir PDF a imágenes y buscar QR
            // Usaremos una librería de Python o herramienta externa
            // Por simplicidad, primero intentaremos extraer el texto del PDF
            
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            Log::info('Texto extraído del PDF (primeros 500 caracteres):', [
                'texto' => substr($text, 0, 500)
            ]);

            // Intentar diferentes patrones de URL del SAT
            $patterns = [
                // Patrón completo con https
                '/https:\/\/siat\.sat\.gob\.mx\/app\/qr\/faces\/pages\/mobile\/validadorqr\.jsf\?[^\s\)\"\'\'<>\]]+/i',
                // Patrón sin https
                '/siat\.sat\.gob\.mx\/app\/qr\/faces\/pages\/mobile\/validadorqr\.jsf\?[^\s\)\"\'\'<>\]]+/i',
                // Patrón más simple
                '/validadorqr\.jsf\?[^\s\)\"\'\'<>\]]+/i',
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $url = $matches[0];
                    // Asegurar que tenga https://
                    if (!str_starts_with($url, 'http')) {
                        $url = 'https://' . $url;
                    }
                    Log::info('URL QR encontrada con patrón: ' . $pattern, ['url' => $url]);
                    return $url;
                }
            }

            // Si no se encuentra en el texto, intentar extraer usando imagenes del PDF
            return $this->extraerQRdeImagenes($pdfPath);

        } catch (\Exception $e) {
            Log::error('Error al extraer QR del PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Intenta extraer el QR de las imágenes del PDF usando zxing-js
     * Esta función requiere que esté instalada la librería de procesamiento de imágenes
     * 
     * @param string $pdfPath
     * @return string|null
     */
    private function extraerQRdeImagenes(string $pdfPath): ?string
    {
        // Esta implementación requeriría:
        // 1. Imagick o similar para extraer imágenes del PDF
        // 2. Librería de lectura de QR (zxing, zbar, etc)
        
        // Por ahora, retornamos null
        // En producción, esto se implementaría con herramientas como:
        // - exec('zbarimg') con la imagen extraída
        // - O un servicio externo de lectura de QR
        
        return null;
    }

    /**
     * Obtiene los datos fiscales desde la página del SAT.
     *
     * @param string $url URL del validador QR del SAT
     * @return array|null
     */
    private function obtenerDatosDelSAT(string $url): ?array
    {
        try {
            // Hacer request a la URL del SAT
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Error al obtener datos del SAT: ' . $response->status());
                return null;
            }

            $html = $response->body();
            
            // Parsear el HTML y extraer datos
            return $this->parsearHTMLdelSAT($html);

        } catch (\Exception $e) {
            Log::error('Error al obtener datos del SAT: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parsea el HTML de la página del SAT y extrae los datos fiscales.
     *
     * @param string $html
     * @return array
     */
    private function parsearHTMLdelSAT(string $html): array
    {
        $datos = [
            'rfc' => null,
            'curp' => null,
            'razon_social' => null,
            'nombre' => null,
            'apellido_paterno' => null,
            'apellido_materno' => null,
            'regimen_fiscal_nombre' => null,
            'direccion_fiscal' => [
                'calle' => null,
                'numero_exterior' => null,
                'numero_interior' => null,
                'colonia' => null,
                'ciudad' => null,
                'estado' => null,
                'codigo_postal' => null,
                'pais' => 'México',
            ],
        ];

        try {
            // Extraer RFC
            if (preg_match('/RFC:\s*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/i', $html, $matches)) {
                $datos['rfc'] = strtoupper(trim($matches[1]));
            }

            // Extraer CURP
            if (preg_match('/CURP:\s*([A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d)/i', $html, $matches)) {
                $datos['curp'] = strtoupper(trim($matches[1]));
            }

            // Extraer Nombre completo (para personas físicas)
            if (preg_match('/Nombre:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['nombre'] = trim($matches[1]);
            }

            if (preg_match('/Apellido Paterno:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['apellido_paterno'] = trim($matches[1]);
            }

            if (preg_match('/Apellido Materno:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['apellido_materno'] = trim($matches[1]);
            }

            // Construir razón social (nombre completo para físicas)
            if ($datos['nombre'] && $datos['apellido_paterno']) {
                $razonSocial = trim(
                    $datos['nombre'] . ' ' . 
                    $datos['apellido_paterno'] . ' ' . 
                    ($datos['apellido_materno'] ?? '')
                );
                $datos['razon_social'] = $razonSocial;
            }

            // Extraer Régimen Fiscal
            if (preg_match('/Régimen:\s*([^\n<]+?)(?:Fecha de alta:|$)/is', $html, $matches)) {
                $datos['regimen_fiscal_nombre'] = trim($matches[1]);
            }

            // Extraer datos de dirección
            if (preg_match('/Entidad Federativa:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['direccion_fiscal']['estado'] = trim($matches[1]);
            }

            if (preg_match('/Municipio o delegación:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['direccion_fiscal']['ciudad'] = trim($matches[1]);
            }

            if (preg_match('/Colonia:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['direccion_fiscal']['colonia'] = trim($matches[1]);
            }

            if (preg_match('/Nombre de la vialidad:\s*([^\n<]+)/i', $html, $matches)) {
                $datos['direccion_fiscal']['calle'] = trim($matches[1]);
            }

            if (preg_match('/Número exterior:\s*([^\n<]+)/i', $html, $matches)) {
                $numExt = trim($matches[1]);
                $datos['direccion_fiscal']['numero_exterior'] = ($numExt !== 'S/N') ? $numExt : null;
            }

            if (preg_match('/Número interior:\s*([^\n<]+)/i', $html, $matches)) {
                $numInt = trim($matches[1]);
                $datos['direccion_fiscal']['numero_interior'] = ($numInt !== 'S/N') ? $numInt : null;
            }

            if (preg_match('/CP:\s*(\d{5})/i', $html, $matches)) {
                $datos['direccion_fiscal']['codigo_postal'] = trim($matches[1]);
            }

        } catch (\Exception $e) {
            Log::error('Error al parsear HTML del SAT: ' . $e->getMessage());
        }

        return $datos;
    }

    /**
     * Obtiene la clave del régimen fiscal a partir del nombre.
     * Esto es un mapeo básico de los regímenes más comunes.
     *
     * @param string $nombreRegimen
     * @return string|null
     */
    public function obtenerClaveRegimen(string $nombreRegimen): ?string
    {
        $regimenes = [
            'General de Ley Personas Morales' => '601',
            'Personas Morales con Fines no Lucrativos' => '603',
            'Sueldos y Salarios e Ingresos Asimilados a Salarios' => '605',
            'Arrendamiento' => '606',
            'Régimen de Enajenación o Adquisición de Bienes' => '607',
            'Demás ingresos' => '608',
            'Consolidación' => '609',
            'Residentes en el Extranjero sin Establecimiento Permanente en México' => '610',
            'Ingresos por Dividendos (socios y accionistas)' => '611',
            'Personas Físicas con Actividades Empresariales y Profesionales' => '612',
            'Ingresos por intereses' => '614',
            'Régimen de los ingresos por obtención de premios' => '615',
            'Sin obligaciones fiscales' => '616',
            'Sociedades Cooperativas de Producción que optan por diferir sus ingresos' => '620',
            'Incorporación Fiscal' => '621',
            'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' => '622',
            'Opcional para Grupos de Sociedades' => '623',
            'Coordinados' => '624',
            'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas' => '625',
            'Régimen Simplificado de Confianza' => '626',
        ];

        foreach ($regimenes as $nombre => $clave) {
            if (stripos($nombreRegimen, $nombre) !== false || 
                stripos($nombre, $nombreRegimen) !== false) {
                return $clave;
            }
        }

        return null;
    }
}
