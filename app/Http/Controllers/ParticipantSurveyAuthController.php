<?php

namespace App\Http\Controllers;

use App\Http\Requests\Surveys\RequestParticipantMagicLinkRequest;
use App\Mail\ParticipantSurveyMagicLinkMail;
use App\Models\Participant;
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
            'redirect' => $this->redirectPath($request->query('redirect')),
        ]);
    }

    public function store(RequestParticipantMagicLinkRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $redirect = $this->redirectPath($request->input('redirect'));

        $participant = Participant::query()->firstOrCreate(
            ['email' => $email],
            ['name' => null],
        );

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

        $target = $this->redirectPath($request->query('redirect'));

        Auth::guard('participant')->login($participant);
        $request->session()->regenerate();

        return redirect()->to($target);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('participant')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('survey.participant.login');
    }

    private function redirectPath(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '/surveys';
        }

        if (! str_starts_with($value, '/') || str_contains($value, '://')) {
            return '/surveys';
        }

        if (str_starts_with($value, '/survey') || str_starts_with($value, '/s/') || $value === '/surveys') {
            return $value;
        }

        return '/surveys';
    }
}
