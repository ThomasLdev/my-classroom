<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Teaching\Domain\Model\Session\Activity;

final class ActivityViewFactory
{
    public function fromActivity(Activity $activity): ActivityView
    {
        return new ActivityView(
            id: (string) $activity->id,
            title: $activity->title,
            done: !$activity->isPlanned(),
            carriedOver: $activity->carriedOverFrom !== null,
            position: $activity->position,
        );
    }
}
