<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\RequestParticipantMagicLinkRequest;
use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Models\Participant;
use App\Support\ParticipantSurveyRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ParticipantSurveyAuthController extends Controller
{
    public function create(Request $request): View
    {
        return view('surveys.participant-login', [
            'redirect' => ParticipantSurveyRedirect::sanitize($request->query('redirect')),
        ]);
    }

    public function store(RequestParticipantMagicLinkRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $redirect = ParticipantSurveyRedirect::sanitize($request->input('redirect'));

        $participant = Participant::query()->where('email', $email)->first();

        if ($participant === null) {
            $participant = Participant::create([
                'email' => $email,
                'name' => null,
            ]);
        }

        if (! $participant->isBlocked()) {
            $signedUrl = URL::temporarySignedRoute(
                'survey.participant.verify',
                now()->addMinutes(60),
                [
                    'participant' => $participant->id,
                    'redirect' => $redirect,
                ],
            );

            Mail::to($email)->send(new ParticipantSurveyMagicLinkMail($signedUrl));
        }

        return back()->with('magicLinkStatus', 'sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $participant = Participant::query()->findOrFail((int) $request->query('participant'));

        if ($participant->isBlocked()) {
            abort(403);
        }

        $target = ParticipantSurveyRedirect::sanitize($request->query('redirect'));

        Auth::guard('participant')->login($participant);
        $request->session()->regenerate();

        return redirect()->to($target);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('participant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('survey.participant.login');
    }
}
