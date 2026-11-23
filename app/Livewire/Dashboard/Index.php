<?php

namespace App\Livewire\Dashboard;

use App\Models\Business;
use App\Models\Website;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

            if ($this->website) {
                if (Schema::hasTable('website_analytics')) {
                    $this->totalViews = DB::table('website_analytics')
                        ->where('website_id', $this->website->id)
                        ->where('event_type', 'page_view')
                        ->count();

                    $this->totalWaClicks = DB::table('website_analytics')
                        ->where('website_id', $this->website->id)
                        ->where('event_type', 'wa_click')
                        ->count();
                }

                if ($this->totalViews === 0 && Schema::hasTable('website_visits')) {
                    $this->totalViews = $this->website->visits()->count();
                }

                if ($this->totalWaClicks === 0 && Schema::hasTable('whatsapp_clicks')) {
                    $this->totalWaClicks = $this->website->whatsappClicks()->count();
                }
            }
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
