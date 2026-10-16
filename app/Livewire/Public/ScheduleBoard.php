<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Services\Cache\TofunimeCacheService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Jadwal Rilis Anime Mingguan - Tofunime')]
class ScheduleBoard extends Component
{
    public string $activeDay = 'all';

    public function setDay(string $day): void
    {
        $this->activeDay = $day;
    }

    public function render(TofunimeCacheService $cache)
    {
        $schedule = $cache->getWeeklySchedule();

        $daysMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            'Random' => 'Jadwal Acak',
        ];

        return view('livewire.public.schedule-board', compact('schedule', 'daysMap'));
    }
}
