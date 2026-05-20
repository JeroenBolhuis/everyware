<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeductParticipantPoints
{
    public function __invoke(Participant $participant, int $points, string $reason): ParticipantPointsHistory
    {
        $reason = trim($reason);

        if ($points < 1) {
            throw new InvalidArgumentException('Het aantal punten moet minimaal 1 zijn.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('Geef een reden op voor de puntenaftrek.');
        }

        return DB::transaction(function () use ($participant, $points, $reason): ParticipantPointsHistory {
            $history = ParticipantPointsHistory::create([
                'participant_id' => $participant->id,
                'amount' => -$points,
                'reason' => $reason,
                'source_type' => null,
                'source_id' => null,
            ]);

            $participant->decrement('current_points', $points);
            $participant->refresh();

            return $history;
        });
    }
}
