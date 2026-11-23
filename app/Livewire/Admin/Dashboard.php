<?php

namespace App\Livewire\Admin;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard Superadmin')]
class Dashboard extends Component
{
    public int $totalUmkm = 0;

    public int $totalPublishedWebsites = 0;

    public int $totalProducts = 0;

    public int $totalVisits = 0;

    public int $totalWaClicks = 0;

    public function mount(): void
    {
        $this->totalUmkm = User::where('role', 'umkm')->count();
        $this->totalPublishedWebsites = Website::where('status', 'published')->count();
        $this->totalProducts = Product::count();
        $this->totalVisits = WebsiteVisit::count();
        $this->totalWaClicks = WhatsappClick::count();
    }

    public function render(): View
    {
        $recentBusinesses = Business::with(['user', 'category', 'website'])
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.admin.dashboard', [
            'recentBusinesses' => $recentBusinesses,
        ]);
    }
}
