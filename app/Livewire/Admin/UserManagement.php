<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Manajemen Pengguna UMKM')]
class UserManagement extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::query()
            ->where('role', 'umkm')
            ->with(['business.category', 'business.website'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhereHas('business', function ($bq) {
                            $bq->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }
}
