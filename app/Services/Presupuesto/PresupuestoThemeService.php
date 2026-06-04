<?php

namespace App\Services\Presupuesto;

/**
 * Fuente única de verdad para temas visuales de presupuestos (PDF y consumidores API).
 */
final class PresupuestoThemeService
{
    public const DEFAULT_THEME_KEY = 'corporativo';

    /** @var array<string, string> Claves antiguas → clave actual */
    private const LEGACY_THEME_ALIASES = [
        'moderno' => 'consultoria-profesional',
        'elegante' => 'premium-lujo',
        'verde' => 'finanzas-confianza',
        'oscuro' => 'reporte-ejecutivo',
    ];

    /**
     * @var array<string, array{name: string, key: string, description: string, variables: array<string, string|float>}>
     */
    private const THEMES = [
        'corporativo' => [
            'name' => 'Azul Institucional',
            'key' => 'corporativo',
            'description' => 'Servicios profesionales, oficinas corporativas y propuestas B2B tradicionales.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f8fafc',
                'color-slate-100' => '#f1f5f9',
                'color-slate-200' => '#e2e8f0',
                'color-slate-400' => '#94a3b8',
                'color-slate-500' => '#64748b',
                'color-slate-600' => '#475569',
                'color-slate-700' => '#334155',
                'color-slate-800' => '#1e293b',
                'color-slate-900' => '#0f172a',
                'color-primary' => '#2563eb',
                'color-primary-dark' => '#1d4ed8',
                'color-primary-soft' => '#eff6ff',
                'color-primary-border' => '#bfdbfe',
                'color-heading' => '#1e3a5f',
                'color-receptor-line' => '#475569',
                'color-row-even' => '#eff6ff',
                'color-paragraph-bg' => '#f8fafc',
                'color-importe-label-bg' => '#f8fafc',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'consultoria-profesional' => [
            'name' => 'Consultoría Profesional',
            'key' => 'consultoria-profesional',
            'description' => 'Consultorías, auditorías y firmas que requieren claridad y autoridad visual.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f8fafc',
                'color-slate-100' => '#f1f5f9',
                'color-slate-200' => '#e2e8f0',
                'color-slate-400' => '#94a3b8',
                'color-slate-500' => '#64748b',
                'color-slate-600' => '#475569',
                'color-slate-700' => '#334155',
                'color-slate-800' => '#1e293b',
                'color-slate-900' => '#0f172a',
                'color-primary' => '#4f46e5',
                'color-primary-dark' => '#4338ca',
                'color-primary-soft' => '#eef2ff',
                'color-primary-border' => '#c7d2fe',
                'color-heading' => '#312e81',
                'color-receptor-line' => '#4c1d95',
                'color-row-even' => '#f5f3ff',
                'color-paragraph-bg' => '#fafafa',
                'color-importe-label-bg' => '#f9fafb',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'finanzas-confianza' => [
            'name' => 'Finanzas y Confianza',
            'key' => 'finanzas-confianza',
            'description' => 'Contabilidad, seguros, cooperativas y servicios donde el verde transmite solidez.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f8fafc',
                'color-slate-100' => '#f1f5f9',
                'color-slate-200' => '#e2e8f0',
                'color-slate-400' => '#94a3b8',
                'color-slate-500' => '#64748b',
                'color-slate-600' => '#475569',
                'color-slate-700' => '#334155',
                'color-slate-800' => '#1e293b',
                'color-slate-900' => '#0f172a',
                'color-primary' => '#047857',
                'color-primary-dark' => '#065f46',
                'color-primary-soft' => '#ecfdf5',
                'color-primary-border' => '#a7f3d0',
                'color-heading' => '#064e3b',
                'color-receptor-line' => '#047857',
                'color-row-even' => '#f0fdf4',
                'color-paragraph-bg' => '#f8fafc',
                'color-importe-label-bg' => '#f0fdf4',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'tecnologia-digital' => [
            'name' => 'Tecnología Digital',
            'key' => 'tecnologia-digital',
            'description' => 'Software, TI, marketing digital y startups con acento contemporáneo.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f8fafc',
                'color-slate-100' => '#f1f5f9',
                'color-slate-200' => '#e2e8f0',
                'color-slate-400' => '#94a3b8',
                'color-slate-500' => '#64748b',
                'color-slate-600' => '#475569',
                'color-slate-700' => '#334155',
                'color-slate-800' => '#0f172a',
                'color-slate-900' => '#020617',
                'color-primary' => '#0ea5e9',
                'color-primary-dark' => '#0284c7',
                'color-primary-soft' => '#f0f9ff',
                'color-primary-border' => '#bae6fd',
                'color-heading' => '#0c4a6e',
                'color-receptor-line' => '#0369a1',
                'color-row-even' => '#f0f9ff',
                'color-paragraph-bg' => '#f8fafc',
                'color-importe-label-bg' => '#f0f9ff',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'construccion-industrial' => [
            'name' => 'Construcción e Infraestructura',
            'key' => 'construccion-industrial',
            'description' => 'Obra civil, maquinaria, logística y proveedores industriales.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#fafaf9',
                'color-slate-100' => '#f5f5f4',
                'color-slate-200' => '#e7e5e4',
                'color-slate-400' => '#a8a29e',
                'color-slate-500' => '#78716c',
                'color-slate-600' => '#57534e',
                'color-slate-700' => '#44403c',
                'color-slate-800' => '#292524',
                'color-slate-900' => '#1c1917',
                'color-primary' => '#ea580c',
                'color-primary-dark' => '#c2410c',
                'color-primary-soft' => '#fff7ed',
                'color-primary-border' => '#fed7aa',
                'color-heading' => '#431407',
                'color-receptor-line' => '#9a3412',
                'color-row-even' => '#fff7ed',
                'color-paragraph-bg' => '#fafaf9',
                'color-importe-label-bg' => '#fafaf9',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'salud-bienestar' => [
            'name' => 'Salud y Bienestar',
            'key' => 'salud-bienestar',
            'description' => 'Clínicas, laboratorios, farmacia y servicios de cuidado personal.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f8fafc',
                'color-slate-100' => '#f1f5f9',
                'color-slate-200' => '#e2e8f0',
                'color-slate-400' => '#94a3b8',
                'color-slate-500' => '#64748b',
                'color-slate-600' => '#475569',
                'color-slate-700' => '#334155',
                'color-slate-800' => '#1e293b',
                'color-slate-900' => '#0f172a',
                'color-primary' => '#0d9488',
                'color-primary-dark' => '#0f766e',
                'color-primary-soft' => '#f0fdfa',
                'color-primary-border' => '#99f6e4',
                'color-heading' => '#134e4a',
                'color-receptor-line' => '#0f766e',
                'color-row-even' => '#f0fdfa',
                'color-paragraph-bg' => '#f8fafc',
                'color-importe-label-bg' => '#f0fdfa',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'legal-compliance' => [
            'name' => 'Legal y Compliance',
            'key' => 'legal-compliance',
            'description' => 'Despachos jurídicos, notarías y documentación formal de alto rigor.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#fafafa',
                'color-slate-100' => '#f4f4f5',
                'color-slate-200' => '#e4e4e7',
                'color-slate-400' => '#a1a1aa',
                'color-slate-500' => '#71717a',
                'color-slate-600' => '#52525b',
                'color-slate-700' => '#3f3f46',
                'color-slate-800' => '#27272a',
                'color-slate-900' => '#18181b',
                'color-primary' => '#7f1d1d',
                'color-primary-dark' => '#991b1b',
                'color-primary-soft' => '#fef2f2',
                'color-primary-border' => '#fecaca',
                'color-heading' => '#450a0a',
                'color-receptor-line' => '#6b2121',
                'color-row-even' => '#faf5f5',
                'color-paragraph-bg' => '#fafafa',
                'color-importe-label-bg' => '#fafafa',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'comercio-retail' => [
            'name' => 'Comercio y Retail',
            'key' => 'comercio-retail',
            'description' => 'Ventas al detalle, distribución y propuestas comerciales dinámicas.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#fffbeb',
                'color-slate-100' => '#fef3c7',
                'color-slate-200' => '#fde68a',
                'color-slate-400' => '#a8a29e',
                'color-slate-500' => '#78716c',
                'color-slate-600' => '#57534e',
                'color-slate-700' => '#44403c',
                'color-slate-800' => '#292524',
                'color-slate-900' => '#1c1917',
                'color-primary' => '#e11d48',
                'color-primary-dark' => '#be123c',
                'color-primary-soft' => '#fff1f2',
                'color-primary-border' => '#fecdd3',
                'color-heading' => '#881337',
                'color-receptor-line' => '#9f1239',
                'color-row-even' => '#fff1f2',
                'color-paragraph-bg' => '#fffbeb',
                'color-importe-label-bg' => '#fffbeb',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'reporte-ejecutivo' => [
            'name' => 'Reporte Ejecutivo',
            'key' => 'reporte-ejecutivo',
            'description' => 'Presentaciones sobrias para directorios, licitaciones y informes formales.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#f9fafb',
                'color-slate-100' => '#f3f4f6',
                'color-slate-200' => '#e5e7eb',
                'color-slate-400' => '#9ca3af',
                'color-slate-500' => '#6b7280',
                'color-slate-600' => '#4b5563',
                'color-slate-700' => '#374151',
                'color-slate-800' => '#1f2937',
                'color-slate-900' => '#111827',
                'color-primary' => '#374151',
                'color-primary-dark' => '#1f2937',
                'color-primary-soft' => '#f3f4f6',
                'color-primary-border' => '#d1d5db',
                'color-heading' => '#111827',
                'color-receptor-line' => '#4b5563',
                'color-row-even' => '#f3f4f6',
                'color-paragraph-bg' => '#f9fafb',
                'color-importe-label-bg' => '#f9fafb',
                'color-importe-value-bg' => '#ffffff',
                'section-line-height' => '1.05',
            ],
        ],
        'premium-lujo' => [
            'name' => 'Premium Corporativo',
            'key' => 'premium-lujo',
            'description' => 'Marcas de alto valor, arquitectura, hospitality y servicios exclusivos.',
            'variables' => [
                'color-white' => '#ffffff',
                'color-slate-50' => '#faf9f7',
                'color-slate-100' => '#f5f0e8',
                'color-slate-200' => '#e8dfd0',
                'color-slate-400' => '#a8a29e',
                'color-slate-500' => '#78716c',
                'color-slate-600' => '#57534e',
                'color-slate-700' => '#44403c',
                'color-slate-800' => '#292524',
                'color-slate-900' => '#1c1917',
                'color-primary' => '#92702a',
                'color-primary-dark' => '#78591f',
                'color-primary-soft' => '#faf6ee',
                'color-primary-border' => '#e8d4a8',
                'color-heading' => '#1c1917',
                'color-receptor-line' => '#57534e',
                'color-row-even' => '#faf6ee',
                'color-paragraph-bg' => '#faf9f7',
                'color-importe-label-bg' => '#faf9f7',
                'color-importe-value-bg' => '#fffdf8',
                'section-line-height' => '1.05',
            ],
        ],
    ];

    /**
     * @return list<array{name: string, key: string, description: string, variables: array<string, string|float>}>
     */
    public function getThemes(): array
    {
        return array_values(self::THEMES);
    }

    /**
     * @return array{name: string, key: string, description: string, variables: array<string, string|float>}
     */
    public function getTheme(string $theme): array
    {
        $key = $this->resolveThemeKey($theme);

        return self::THEMES[$key];
    }

    /**
     * @return array{name: string, key: string, description: string, variables: array<string, string|float>}
     */
    public function getDefaultTheme(): array
    {
        return self::THEMES[self::DEFAULT_THEME_KEY];
    }

    public function getDefaultThemeKey(): string
    {
        return self::DEFAULT_THEME_KEY;
    }

    public function themeExists(string $theme): bool
    {
        $key = $this->normalizeKey($theme);

        return isset(self::THEMES[$key]) || isset(self::LEGACY_THEME_ALIASES[$key]);
    }

    public function resolveThemeKey(?string $theme): string
    {
        if ($theme === null || $theme === '') {
            return self::DEFAULT_THEME_KEY;
        }

        $key = $this->normalizeKey($theme);

        if (isset(self::LEGACY_THEME_ALIASES[$key])) {
            $key = self::LEGACY_THEME_ALIASES[$key];
        }

        return isset(self::THEMES[$key]) ? $key : self::DEFAULT_THEME_KEY;
    }

    public function generateCssVariables(string $theme): string
    {
        $variables = $this->getTheme($theme)['variables'];
        $lines = [];

        foreach ($variables as $name => $value) {
            $lines[] = sprintf('    --%s:%s;', $name, $this->formatCSSValue($value));
        }

        return ":root{\n".implode("\n", $lines)."\n}";
    }

    /**
     * Estilos de encabezado de tabla con colores literales (DomPDF no aplica bien var() en celdas).
     */
    public function generateTableHeaderCss(string $theme): string
    {
        $v = $this->getTheme($theme)['variables'];
        $bg = $v['color-primary'];
        $text = $v['color-white'];
        $border = $v['color-primary-dark'];

        return implode("\n", [
            '.tw-table thead tr{background-color:'.$bg.'!important;}',
            '.tw-table thead th{',
            'background-color:'.$bg.'!important;',
            'color:'.$text.'!important;',
            'border:1px solid '.$border.'!important;',
            '-webkit-print-color-adjust:exact;',
            'print-color-adjust:exact;',
            '}',
        ]);
    }

    /**
     * @return array{bg: string, text: string, border: string}
     */
    public function tableHeaderColors(string $theme): array
    {
        $v = $this->getTheme($theme)['variables'];

        return [
            'bg' => (string) $v['color-primary'],
            'text' => (string) $v['color-white'],
            'border' => (string) $v['color-primary-dark'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function variablesAsCssMap(string $theme): array
    {
        $variables = $this->getTheme($theme)['variables'];
        $map = [];

        foreach ($variables as $name => $value) {
            $map['--'.$name] = (string) $this->formatCSSValue($value);
        }

        return $map;
    }

    private function normalizeKey(string $theme): string
    {
        return strtolower(trim($theme));
    }

    private function formatCSSValue(string|float $value): string
    {
        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }

        return $value;
    }
}
