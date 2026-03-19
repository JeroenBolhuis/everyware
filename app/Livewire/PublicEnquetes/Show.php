<?php

namespace App\Livewire\PublicEnquetes;

use App\Models\Enquete;
use Livewire\Component;

class Show extends Component
{
    public Enquete $enquete;

    public function mount(Enquete $enquete): void
    {
        abort_unless($enquete->is_published, 404);

        $this->enquete = $enquete;
    }

    public function render()
    {
        return view('livewire.public-enquetes.show')
            ->layout('layouts.public', ['title' => $this->enquete->title]);
    }
}
