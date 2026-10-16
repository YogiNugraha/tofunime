<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Models\Anime;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $isOpen = false;

    public function openModal(): void
    {
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    public function render()
    {
        $results = collect();

        if (strlen(trim($this->query)) >= 2) {
            $q = trim($this->query);
            $results = Anime::with('studio')
                ->where(function ($sub) use ($q): void {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('english_title', 'like', "%{$q}%")
                        ->orWhere('japanese_title', 'like', "%{$q}%");
                })
                ->orderByDesc('rating_score')
                ->take(8)
                ->get();
        }

        return view('livewire.public.global-search', compact('results'));
    }
}
