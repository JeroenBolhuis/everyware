<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Surveys\BuildSurveyFeedbackExport;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class SurveyFeedbackExportController extends Controller
{
    public function __invoke(
        Request $request,
        Survey $survey,
        BuildSurveyFeedbackExport $buildSurveyFeedbackExport,
    ): Response
    {
        Gate::authorize('view', $survey);

        $format = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless($buildSurveyFeedbackExport->supports($format), 404);

        return response($buildSurveyFeedbackExport->build($survey, $format), 200, [
            'Content-Type' => $buildSurveyFeedbackExport->contentType($format),
            'Content-Disposition' => 'attachment; filename="'.$buildSurveyFeedbackExport->fileName($survey, $format).'"',
        ]);
    }
}
