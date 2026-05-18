<?php

namespace App\Http\Middleware;

use App\Support\ParticipantSurveyRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfParticipantAuthenticated
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('participant')) {
            return redirect()->to(
                ParticipantSurveyRedirect::sanitize($request->query('redirect'))
            );
        }

        return $next($request);
    }
}
