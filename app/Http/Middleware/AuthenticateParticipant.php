<?php

namespace App\Http\Middleware;

use App\Support\ParticipantSurveyRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateParticipant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('participant')) {
            return redirect()->guest(
                route('survey.participant.login', [
                    'redirect' => ParticipantSurveyRedirect::sanitize('/'.$request->path()),
                ])
            );
        }

        return $next($request);
    }
}
