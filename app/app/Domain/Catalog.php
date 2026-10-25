<?php

namespace App\Domain;

/** Listas fixas do protótipo. */
class Catalog
{
    public const SYMPTOMS = ['Dor de cabeça', 'Enjoo', 'Ansiedade', 'Cansaço', 'Tontura', 'Constipação', 'Insônia', 'Azia', 'Irritabilidade', 'Fome excessiva', 'Boca seca', 'Diarreia'];

    public const INTENSITY = ['Leve', 'Moderada', 'Forte'];

    public const INT_TONE = ['Leve' => 'ok', 'Moderada' => 'warn', 'Forte' => 'bad'];

    public const MODALITIES = ['Força' => ['Musculação', 'Funcional', 'Pilates', 'Calistenia'], 'Aeróbico' => ['Caminhada', 'Corrida', 'Bicicleta', 'Natação', 'Dança', 'Elíptico']];

    public const WORKOUT_INTENSITY = ['Leve', 'Moderada', 'Intensa'];

    public const MOODS = [
        1 => ['icon' => 'face-sad-tear', 'l' => 'Muito mal'],
        2 => ['icon' => 'face-frown', 'l' => 'Mal'],
        3 => ['icon' => 'face-meh', 'l' => 'Neutra'],
        4 => ['icon' => 'face-smile', 'l' => 'Bem'],
        5 => ['icon' => 'face-laugh-beam', 'l' => 'Muito bem'],
    ];

    public const JOURNAL_TAGS = ['Trabalho', 'Rotina', 'Emoções', 'Família', 'Sono', 'Alimentação', 'Corpo'];

    public const ANGLES = ['Frente', 'Lado', 'Costas'];

    public const ANGLE_ICON = ['Frente' => 'person', 'Lado' => 'person-walking', 'Costas' => 'child-reaching'];

    public const EXAM_KINDS = ['Bioimpedância', 'Exame de sangue', 'Imagem', 'Outro'];

    public const OBJECTIVES = ['Emagrecimento', 'Ganho de massa muscular', 'Composição corporal', 'Saúde metabólica', 'Reeducação alimentar', 'Controle glicêmico'];

    public const APPOINTMENT_TYPES = ['Primeira consulta', 'Retorno', 'Retorno mensal', 'Reavaliação'];

    public const FVL = ['frutas' => ['Frutas', 'apple-whole'], 'verduras' => ['Verduras', 'leaf'], 'legumes' => ['Legumes', 'carrot']];

    public const REMINDERS = [
        'water' => ['icon' => 'glass-water', 'l' => 'Beber água', 'h' => 'A cada 2 horas, das 8h às 20h', 'module' => 'metas', 'default' => true],
        'habits' => ['icon' => 'list-check', 'l' => 'Marcar metas do dia', 'h' => 'Todos os dias às 20h30', 'module' => 'metas', 'default' => true],
        'bio' => ['icon' => 'weight-scale', 'l' => 'Bioimpedância', 'h' => 'Todo início de mês', 'module' => 'bioimpedancia', 'default' => true],
        'therapy' => ['icon' => 'brain', 'l' => 'Sessão de terapia', 'h' => '1 hora antes', 'module' => 'saude_mental', 'default' => true],
        'workout' => ['icon' => 'dumbbell', 'l' => 'Registrar treino', 'h' => 'Seg, qua e sex às 19h', 'module' => 'atividade', 'default' => false],
        'journal' => ['icon' => 'feather', 'l' => 'Desabafo da semana', 'h' => 'Domingo às 18h', 'module' => 'saude_mental', 'default' => false],
    ];

    public static function mood(?int $v): array
    {
        return self::MOODS[$v] ?? self::MOODS[3];
    }

    public static function moodTone(int $v): string
    {
        return $v >= 4 ? 'ok' : ($v === 3 ? 'warn' : 'bad');
    }
}
