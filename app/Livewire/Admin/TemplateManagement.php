<?php

namespace App\Livewire\Admin;

use App\Models\Template;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Manajemen Template')]
class TemplateManagement extends Component
{
    public function toggleStatus(int $templateId): void
    {
        $template = Template::findOrFail($templateId);
        $template->is_active = ! $template->is_active;
        $template->save();

        session()->flash('success', "Status template '{$template->name}' berhasil diperbarui menjadi ".($template->is_active ? 'Aktif' : 'Nonaktif').'.');
    }

    public function render(): View
    {
        $templates = Template::withCount('websites')->get();

        return view('livewire.admin.template-management', [
            'templates' => $templates,
        ]);
    }
}
