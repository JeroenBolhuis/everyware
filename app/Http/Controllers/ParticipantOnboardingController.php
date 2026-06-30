<?php

namespace App\Http\Controllers;

use App\Enums\Academy;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticipantOnboardingController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->user('participant');

        if ($participant->onboarded_at === null) {
            $validated = $request->validate([
                'academy' => ['required', 'string', Rule::in(Academy::keys())],
            ]);

            $participant->forceFill([
                'academy' => $validated['academy'],
                'onboarded_at' => now(),
            ])->save();
        }

        return back();
    }
}
