<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Définition du planificateur de tâches (aucune tâche planifiée pour le MVP).
     */
    protected function schedule(Schedule $schedule): void
    {
        //
    }

    /**
     * Chargement des commandes et des routes console.
     */
    protected function commands(): void
    {
        $commandsPath = app_path('Console/Commands');

        if (is_dir($commandsPath)) {
            $this->load($commandsPath);
        }

        require base_path('routes/console.php');
    }
}
