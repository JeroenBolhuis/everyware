<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParticipantOnboardingController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var Participant $participant */
        $participant = $request->user('participant');

        if ($participant->onboarded_at === null) {
            $participant->forceFill([
                'onboarded_at' => now(),
            ])->save();
        }

        return back();
    }
}
