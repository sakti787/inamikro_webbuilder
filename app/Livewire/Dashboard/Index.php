<?php

namespace App\Livewire\Dashboard;

use App\Models\Business;
use App\Models\Website;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('Ringkasan Dashboard')]
class Index extends Component
{
    public ?Business $business = null;

    public ?Website $website = null;

    public int $totalProducts = 0;

    public int $totalViews = 0;

    public int $totalWaClicks = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->business = $user?->business;

        if ($this->business) {
            $this->website = $this->business->website()->with('template')->first();

            $this->totalProducts = $this->business->products()
                ->where('is_available', true)
                ->count();

            $this->totalViews = $this->website ? $this->website->visits()->count() : 0;
            $this->totalWaClicks = $this->website ? $this->website->whatsappClicks()->count() : 0;
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
