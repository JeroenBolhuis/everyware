<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPointsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $participant = $request->user('participant');

        $responses = $participant->surveyResponses()
            ->with(['survey', 'participantPointsHistories'])
            ->latest('submitted_at')
            ->get();

        return view('student.points', [
            'participant' => $participant,
            'responses' => $responses,
        ]);
    }
}
