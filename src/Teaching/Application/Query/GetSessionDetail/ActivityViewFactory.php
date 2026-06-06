<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Teaching\Domain\Model\Session\Activity;
use App\Teaching\Domain\Model\Session\ActivityId;

final class ActivityViewFactory
{
    public function fromActivity(Activity $activity): ActivityView
    {
        return new ActivityView(
            id: (string) $activity->id,
            title: $activity->title,
            done: ! $activity->isPlanned(),
            carriedOver: $activity->carriedOverFrom instanceof ActivityId,
            position: $activity->position,
        );
    }
}
