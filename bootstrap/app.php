<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('survey/*') || $request->is('s/*') || $request->is('student/punten')) {
                return route('survey.participant.login', [
                    'redirect' => '/'.$request->path(),
                ]);
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->is('survey/deelnemer/inloggen')) {
                return '/surveys';
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function ($exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('survey/*') || $request->is('s/*')) {
                return redirect()->route('survey.participant.login');
            }
        });

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            return back()
                ->withInput($request->except([]))
                ->withErrors([
                    'questions' => 'De upload is te groot. Gebruik kleinere afbeeldingen. Per afbeelding geldt maximaal 2 MB en in totaal maximaal 5 MB.',
                ]);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            logger()->error('Unhandled exception summary', [
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'previous_exception_class' => $exception->getPrevious() ? $exception->getPrevious()::class : null,
                'previous_message' => $exception->getPrevious()?->getMessage(),
            ]);
        });
    })
    ->create();
