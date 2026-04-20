<?php

namespace App\Livewire\Admin;

use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TablaVendedores extends Component
{
    use WithPagination;

    public string $status = '';

    public function render(): View
    {
        return view('livewire.admin.tabla-vendedores', [
            'vendors' => Vendor::query()
                ->with('user')
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->latest()
                ->paginate(15),
        ]);
    }
}

