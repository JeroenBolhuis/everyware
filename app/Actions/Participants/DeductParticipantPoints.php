<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Models\ParticipantPointsHistory;
use InvalidArgumentException;

class DeductParticipantPoints
{
    private AdjustParticipantPoints $adjustParticipantPoints;

    public function __construct(?AdjustParticipantPoints $adjustParticipantPoints = null)
    {
        $this->adjustParticipantPoints = $adjustParticipantPoints ?? app(AdjustParticipantPoints::class);
    }

    public function __invoke(Participant $participant, int $points, string $reason): ParticipantPointsHistory
    {
        $reason = trim($reason);

        if ($points < 1) {
            throw new InvalidArgumentException('Het aantal punten moet minimaal 1 zijn.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('Geef een reden op voor de puntenaftrek.');
        }

        return ($this->adjustParticipantPoints)($participant, -$points, $reason);
    }
}
