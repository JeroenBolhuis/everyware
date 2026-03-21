<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ContactInformationSubmission extends Model
{
    use HasFactory;
    protected $fillable = [
        'survey_id',
        'survey_response_id',
        'name',
        'email',
        'phone',
        'note',
    ];
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }
}