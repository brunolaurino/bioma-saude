<?php

use Illuminate\Support\Facades\Schedule;

// Agendador (no servidor: "php artisan schedule:run" a cada minuto, ou o contêiner "scheduler").
Schedule::command('bioma:lembretes')->everyFifteenMinutes()->timezone('America/Sao_Paulo')->withoutOverlapping();
Schedule::command('bioma:retencao')->dailyAt('03:30')->timezone('America/Sao_Paulo');
Schedule::command('bioma:auditoria')->dailyAt('04:00')->timezone('America/Sao_Paulo');
