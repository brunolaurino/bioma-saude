<?php

namespace App\Domain;

/** Indicadores de bioimpedância, faixas de validação e tendências (mesmas regras do protótipo). */
class Indicators
{
    public const ALL = [
        'w' => ['l' => 'Peso corporal', 's' => 'Peso', 'u' => 'kg', 'dir' => 'down', 'dec' => 1, 'icon' => 'weight-scale', 'min' => 30, 'max' => 250, 'ph' => '78,8'],
        'f' => ['l' => 'Percentual de gordura', 's' => 'Gordura', 'u' => '%', 'dir' => 'down', 'dec' => 1, 'icon' => 'droplet', 'min' => 3, 'max' => 70, 'ph' => '33,1'],
        'm' => ['l' => 'Massa muscular esquelética', 's' => 'Músculo', 'u' => 'kg', 'dir' => 'up', 'dec' => 1, 'icon' => 'dumbbell', 'min' => 10, 'max' => 80, 'ph' => '27,5'],
        'v' => ['l' => 'Gordura visceral', 's' => 'Visceral', 'u' => 'nível', 'dir' => 'down', 'dec' => 0, 'icon' => 'bullseye', 'min' => 1, 'max' => 30, 'ph' => '8'],
        't' => ['l' => 'Taxa de Metabolismo Basal', 's' => 'TMB', 'u' => 'kcal', 'dir' => 'up', 'dec' => 0, 'icon' => 'fire', 'min' => 800, 'max' => 4000, 'ph' => '1.561'],
    ];

    // Faixa de referência genérica [normal mín, normal máx, escala mín, escala máx].
    // Serve só de orientação visual; a avaliação clínica é do profissional.
    public const RANGES = ['w' => [51, 69, 40, 110], 'f' => [18, 28, 10, 50], 'm' => [22.5, 27.5, 15, 40], 'v' => [1, 9, 1, 20], 't' => [1350, 1650, 1100, 2200]];

    public const TONE = [
        'ok' => ['label' => 'Progresso', 'icon' => 'circle-check'],
        'warn' => ['label' => 'Estável', 'icon' => 'circle-pause'],
        'bad' => ['label' => 'Atenção', 'icon' => 'triangle-exclamation'],
    ];

    public static function get(string $k): array
    {
        return self::ALL[$k] + ['k' => $k];
    }

    /** Tendência do último registro contra o anterior. */
    public static function trend(array $vals, string $dir): array
    {
        $n = count($vals);
        if ($n < 2) {
            return ['d' => 0.0, 'tone' => 'warn'];
        }
        $d = $vals[$n - 1] - $vals[$n - 2];
        $rel = abs($d) / (abs($vals[$n - 2]) ?: 1);
        $good = $dir === 'down' ? $d < 0 : $d > 0;

        return ['d' => $d, 'tone' => $rel < 0.004 ? 'warn' : ($good ? 'ok' : 'bad')];
    }

    public static function toneOfDelta(float $d, string $dir): string
    {
        if (abs($d) < 1e-9) {
            return 'warn';
        }

        return ($dir === 'down' ? $d < 0 : $d > 0) ? 'ok' : 'bad';
    }

    public static function rangeTone(string $k, float $v): string
    {
        [$lo, $hi] = self::RANGES[$k];
        if ($v >= $lo && $v <= $hi) {
            return 'ok';
        }
        $dir = self::ALL[$k]['dir'];
        if ($dir === 'up' && $v > $hi) {
            return 'ok';
        }
        $bad = $dir === 'down' ? $v > $hi : $v < $lo;

        return $bad ? (in_array($k, ['m', 't'], true) ? 'warn' : 'bad') : 'warn';
    }

    public static function toneLabel(string $tone): string
    {
        return self::TONE[$tone]['label'] ?? '';
    }
}
