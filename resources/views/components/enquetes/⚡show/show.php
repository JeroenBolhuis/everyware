<?php

use Livewire\Component;
use App\Models\Enquete;

new class extends Component
{
    public Enquete $enquete;

    public function mount(Enquete $enquete): void
    {
        abort_unless($enquete->is_published, 404);

        $this->enquete = $enquete;
    }
};