<?php

namespace App\Livewire\Admin;

use App\Support\AdminMode;
use Livewire\Component;

class ModeSwitcher extends Component
{
    public string $mode = AdminMode::BEGINNER;

    public function mount(): void
    {
        $this->mode = AdminMode::get();
    }

    public function switchMode(string $mode): void
    {
        AdminMode::set($mode);
        $this->mode = AdminMode::get();

        $this->dispatch('mode-switched');
    }

    public function render()
    {
        return view('livewire.admin.mode-switcher', [
            'mode' => $this->mode,
        ]);
    }
}
