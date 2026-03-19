<?php

namespace App\Livewire\PublicEnquetes;

use App\Models\Enquete;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Index extends Component
{
    /** @var Collection<int, Enquete> */
    public Collection $enquetes;

    public function mount(): void
    {
        $this->enquetes = Enquete::query()
            ->where('is_published', true)
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.public-enquetes.index')
            ->layout('layouts.public', ['title' => __('Enquetes')]);
    }
}
