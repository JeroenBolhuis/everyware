<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdjustParticipantPoints
{
    public function __invoke(Participant $participant, int $amount, string $reason): ParticipantPointsHistory
    {
        $reason = trim($reason);

        if ($amount === 0) {
            throw new InvalidArgumentException('De puntenmutatie mag niet nul zijn.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('Geef een reden op voor de puntenmutatie.');
        }

        return DB::transaction(function () use ($participant, $amount, $reason): ParticipantPointsHistory {
            $participant = Participant::query()
                ->whereKey($participant)
                ->lockForUpdate()
                ->firstOrFail();

            if ($participant->current_points + $amount < 0) {
                throw new InvalidArgumentException('De deelnemer heeft niet genoeg punten.');
            }

            $history = ParticipantPointsHistory::create([
                'participant_id' => $participant->id,
                'amount' => $amount,
                'reason' => $reason,
                'source_type' => null,
                'source_id' => null,
            ]);

            $participant->increment('current_points', $amount);
            $participant->refresh();

            return $history;
        });
    }
}
