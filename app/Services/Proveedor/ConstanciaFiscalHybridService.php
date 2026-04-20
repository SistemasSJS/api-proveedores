<?php

namespace App\Services\Proveedor;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;

class ConstanciaFiscalHybridService
{
  public function extraerDatos(string $pdfPath): array
  {
    Log::info("Intentando extraer datos fiscales de: $pdfPath");

    $texto = $this->extraerTextoPDF($pdfPath);
    $texto = $this->limpiarTexto($texto);

    Log::info('Texto limpiado:', ['texto' => $texto]);

    // 🔹 Separar bloques correctamente
    $bloques = $this->separarBloques($texto);

    Log::info('Bloques extraídos:', ['bloques' => $bloques]);

    $identidadTexto = $bloques['identidad'] ?? '';
    $domicilioTexto = $bloques['domicilio'] ?? '';

    // Fallback: algunos formatos del SAT no delimitan bien los bloques.
    $identidad = $this->normalizarCamposTexto(
      $this->extraerIdentidad($identidadTexto !== '' ? $identidadTexto : $texto)
    );
    $domicilio = $this->normalizarCamposTexto(
      $this->extraerDomicilio($domicilioTexto !== '' ? $domicilioTexto : $texto)
    );
    $regimenes = $this->extraerRegimenes($texto);

    Log::info('Identidad extraída:', ['identidad' => $identidad]);
    Log::info('Domicilio extraído:', ['domicilio' => $domicilio]);
    Log::info('Regímenes extraídos:', ['regimenes' => $regimenes]);

    return [
      ...$identidad,
      ...$domicilio,
      'regimenes' => $regimenes,
      'pais' => 'México'
    ];
  }

  // =============================
  // 📄 PDF
  // =============================
  private function extraerTextoPDF(string $path): string
  {
    try {
      $parser = new Parser();
      $pdf = $parser->parseFile($path);
      $text = $pdf->getText();

      if (trim($text)) return $text;
    } catch (\Throwable $e) {
    }

    return Pdf::getText($path);
  }

  // =============================
  // 🧼 LIMPIEZA
  // =============================
  private function limpiarTexto(string $texto): string
  {
    // separar palabras pegadas tipo "Nombrede"
    $texto = preg_replace('/([a-z])([A-Z])/', '$1 $2', $texto);

    // limpiar caracteres invisibles
    $texto = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $texto);

    // espacios
    $texto = preg_replace('/\s+/', ' ', $texto);

    return trim($texto);
  }

  // =============================
  // 🧱 BLOQUES
  // =============================
  private function separarBloques(string $texto): array
  {
    return [
      'identidad' => $this->matchBlock(
        '/Datos de Identificación del Contribuyente:?(.*?)(?:Datos del domicilio registrado|Domicilio registrado)/si',
        $texto
      ),
      'domicilio' => $this->matchBlock(
        '/(?:Datos del domicilio registrado(?:\s+en\s+el\s+RFC)?|Domicilio registrado):?(.*?)(?:Actividades Económicas|Regímenes|Características fiscales)/si',
        $texto
      ),
    ];
  }

  private function matchBlock($regex, $texto): string
  {
    preg_match($regex, $texto, $m);
    return trim($m[1] ?? '');
  }

  // =============================
  // 👤 IDENTIDAD
  // =============================
  private function extraerIdentidad(string $texto): array
  {
    return [
      'rfc' => $this->match('/RFC:\s*([A-Z0-9]+)/', $texto),
      'curp' => $this->match('/CURP:\s*([A-Z0-9]+)/', $texto),

      'nombre' => $this->match('/Nombre\(s\):\s*(.+?)(?=\s*Primer Apellido:|\s*CURP:|\s*RFC:|\s*Fecha\s*inicio\s*de\s*operaciones:|\s*Fechainiciodeoperaciones:|$)/u', $texto),
      'primer_apellido' => $this->match('/Primer Apellido:\s*(.+?)(?=\s*Segundo Apellido:|\s*CURP:|\s*RFC:|\s*Fecha\s*inicio\s*de\s*operaciones:|\s*Fechainiciodeoperaciones:|$)/u', $texto),
      'segundo_apellido' => $this->match('/Segundo Apellido:\s*(.+?)(?=\s*CURP:|\s*RFC:|\s*Datos del domicilio registrado|\s*Domicilio registrado|\s*Fecha\s*inicio\s*de\s*operaciones:|\s*Fechainiciodeoperaciones:|\s*Estatus\s*en\s*el\s*padr[oó]n:|\s*Estatusenelpadr[oó]n:|\s*Nombre\s*Comercial:|\s*NombreComercial:|$)/u', $texto),

      'razon_social' => null,
    ];
  }

  // =============================
  // 🏠 DOMICILIO
  // =============================
  private function extraerDomicilio(string $texto): array
  {
    return [
      'codigo_postal' => $this->match('/Código Postal:\s*(\d{5})/', $texto),
      'tipo_vialidad' => $this->match('/Tipo de Vialidad:\s*(.+?)(?=\s*Nombre de Vialidad:|\s*Número Exterior:|$)/u', $texto),
      'nombre_vialidad' => $this->match('/Nombre de Vialidad:\s*(.+?)(?=\s*Número Exterior:|\s*Número Interior:|\s*Colonia:|$)/u', $texto),

      'numero_exterior' => $this->match('/Número Exterior:\s*([A-Z0-9\/]+)/', $texto),
      'numero_interior' => $this->match('/Número Interior:\s*([A-Z0-9\/]+)/', $texto),

      'colonia' => $this->match('/Colonia:\s*(.+?)(?=\s*Localidad:|\s*Municipio|\\s*Entidad Federativa:|$)/u', $texto),
      'localidad' => $this->match('/Localidad:\s*(.+?)(?=\s*Municipio|\\s*Entidad Federativa:|$)/u', $texto),
      'municipio_delegacion' => $this->match('/Municipio.*?:\s*(.+?)(?=\s*Entidad Federativa:|\s*Entre Calle:|\s*Y Calle:|$)/u', $texto),
      'entidad_federativa' => $this->match('/Entidad Federativa:\s*(.+?)(?=\s*Entre Calle:|\s*Y Calle:|\s*Código Postal:|$)/u', $texto),

      'entre_calle' => $this->match('/Entre Calle:\s*(.+?)(?=\s*Y Calle:|$)/u', $texto),
      'y_calle' => $this->match('/Y Calle:\s*(.+?)(?=\s*Características fiscales:|\s*Regímenes:|$)/u', $texto),

      // normalizados
      'calle' => $this->match('/Nombre de Vialidad:\s*(.+?)(?=\s*Número Exterior:|\s*Número Interior:|\s*Colonia:|$)/u', $texto),
      'ciudad' => $this->match('/Municipio.*?:\s*(.+?)(?=\s*Entidad Federativa:|\s*Entre Calle:|\s*Y Calle:|$)/u', $texto),
      'estado' => $this->match('/Entidad Federativa:\s*(.+?)(?=\s*Entre Calle:|\s*Y Calle:|\s*Código Postal:|$)/u', $texto),
    ];
  }

  // =============================
  // 💼 REGÍMENES (FIX REAL)
  // =============================
  private function extraerRegimenes(string $texto): array
  {
    $bloqueRegimenes = $this->matchBlock(
      '/(?:Regímenes:|Régimenes:|Régimen fiscal)(.*?)(?:Obligaciones:|Actividades Económicas|Datos informativos|$)/isu',
      $texto
    );

    $fuente = $bloqueRegimenes !== '' ? $bloqueRegimenes : $texto;

    preg_match_all(
      '/Régimen\s+(.*?)\s+(\d{2}\/\d{2}\/\d{4})/iu',
      $fuente,
      $matches,
      PREG_SET_ORDER
    );

    $regimenes = [];

    foreach ($matches as $m) {
      $nombre = $this->normalizarNombreRegimen($m[1] ?? '');

      if ($nombre === '') {
        continue;
      }

      $clave = $this->mapClaveRegimen($nombre);

      $regimenes[] = [
        'clave' => $clave,
        'nombre' => $nombre,
        'fecha_alta' => $m[2] ?? null
      ];
    }

    return $regimenes;
  }

  private function normalizarNombreRegimen(string $nombre): string
  {
    $nombre = trim($nombre);

    // Algunos PDFs mezclan encabezados de columnas en el nombre del régimen.
    $nombre = preg_replace('/\bFecha\s+Inicio\b/iu', ' ', $nombre);
    $nombre = preg_replace('/\bFecha\s+Fin\b/iu', ' ', $nombre);
    $nombre = preg_replace('/\bRégimen\s+de\b/iu', ' ', $nombre);
    $nombre = preg_replace('/\bRégimen\b/iu', ' ', $nombre);
    $nombre = preg_replace('/^(de|del|la|las|los)\s+/iu', '', $nombre);
    $nombre = preg_replace('/\bAm[aá]stardareld[ií]a.*$/iu', ' ', $nombre);
    $nombre = preg_replace('/\bA\s+m[aá]s\s+tardar\s+el\s+d[ií]a.*$/iu', ' ', $nombre);
    $nombre = preg_replace('/\s+/', ' ', $nombre);
    $nombre = trim($nombre);

    // Descartar nombres claramente ruidosos.
    if (mb_strlen($nombre) < 4) {
      return '';
    }

    if (preg_match('/^(fech|estatus|nombrecomercial|obligaciones?)$/iu', str_replace(' ', '', $nombre))) {
      return '';
    }

    $nombre = preg_replace('/\s+/u', ' ', $nombre);

    return trim($nombre);
  }

  private function mapClaveRegimen(string $nombre): ?string
  {
    $map = [
      'Sueldos y Salarios' => '605',
      'Actividades Empresariales y Profesales' => '612',
      'Arrendamiento' => '606',
      'RIF' => '621',
      'RESICO' => '626',
    ];

    foreach ($map as $k => $v) {
      if (stripos($nombre, $k) !== false) {
        return $v;
      }
    }

    return null;
  }

  // =============================
  // 🔍 HELPER
  // =============================
  private function match($regex, $texto)
  {
    preg_match($regex, $texto, $m);
    if (!isset($m[1])) {
      return null;
    }

    return $this->limpiarValorExtraido($m[1]);
  }

  private function limpiarValorExtraido(string $valor): ?string
  {
    $valor = preg_replace('/Página\s*\[\d+\]\s*de\s*\[\d+\]/iu', ' ', $valor);
    $valor = preg_replace('/\bFechainiciodeoperaciones:.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bEstatusenelpadr[oó]n:.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bFechade[uú]ltimocambiodeestado:.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bNombreComercial:.*$/iu', ' ', $valor);

    // Etiquetas OCR pegadas que suelen contaminar al final.
    $valor = preg_replace('/\bNombredela\b.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bNombredel\b.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bYCalle:.*$/iu', ' ', $valor);
    $valor = preg_replace('/\bEntreCalle:.*$/iu', ' ', $valor);

    // Conservar espacios internos; solo limpiar bordes.
    $valor = trim($valor);

    return $valor !== '' ? $valor : null;
  }

  private function normalizarCamposTexto(array $datos): array
  {
    $camposTexto = [
      'nombre',
      'primer_apellido',
      'segundo_apellido',
      'tipo_vialidad',
      'nombre_vialidad',
      'colonia',
      'localidad',
      'municipio_delegacion',
      'entidad_federativa',
      'entre_calle',
      'y_calle',
      'calle',
      'ciudad',
      'estado',
    ];

    foreach ($camposTexto as $campo) {
      if (!isset($datos[$campo]) || !is_string($datos[$campo])) {
        continue;
      }

      $datos[$campo] = $this->humanizarTextoPegado($datos[$campo], $campo);
    }

    return $datos;
  }

  private function humanizarTextoPegado(string $valor, string $campo): string
  {
    $valor = trim($valor);

    if ($valor === '' || preg_match('/\s/u', $valor)) {
      return $valor;
    }

    // Prefijos comunes de domicilio que suelen venir pegados.
    $valor = preg_replace('/^(FRACC\.)([A-ZÁÉÍÓÚÑ])/u', '$1 $2', $valor);
    $valor = preg_replace('/^(CALLE|AVENIDA|AV|PROLONGACION|PRIVADA|BLVD|BOULEVARD|COLONIA|LOCALIDAD|MUNICIPIO|CIUDAD|BARRIO|EJIDO)([A-ZÁÉÍÓÚÑ])/u', '$1 $2', $valor);
    $valor = preg_replace('/^(LOS|LAS|SAN|SANTA)([A-ZÁÉÍÓÚÑ])/u', '$1 $2', $valor);

    // Nombres compuestos frecuentes cuando OCR/PDF los pega.
    if ($campo === 'nombre') {
      $valor = preg_replace('/^(JOSE|JOSÉ|MARIA|MARÍA|LUIS|JULIO|JUAN|MIGUEL|CARLOS|ANA|ANGEL|ÁNGEL)([A-ZÁÉÍÓÚÑ]{3,})$/u', '$1 $2', $valor);
    }

    return $valor;
  }
}
