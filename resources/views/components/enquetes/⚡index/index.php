<?php

use Livewire\Component;
use App\Models\Enquete;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
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
};