<?php

namespace App\Models;

use Database\Factories\ParticipantIdentityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stored on the `personal` database connection.
 *
 * This model is the ONLY place where participant email addresses are persisted.
 * All access must go through App\Services\ParticipantService, never direct
 * Eloquent queries from controllers or other models.
 *
 * @property int $id
 * @property int $participant_id
 * @property string $email
 */
class ParticipantIdentity extends Model
{
    /** @use HasFactory<ParticipantIdentityFactory> */
    use HasFactory;

    /** Points to the separate personal-data database. */
    protected $connection = 'personal';

    protected $fillable = [
        'participant_id',
        'email',
    ];

    public function participant(): BelongsTo
    {
        // Cross-connection relationship: resolved in ParticipantService, not via eager loading.
        return $this->setConnection('mysql')->belongsTo(Participant::class);
    }
}
