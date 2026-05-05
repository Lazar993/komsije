<?php

declare(strict_types=1);

namespace App\Filament\Resources\Polls\Pages;

use App\Events\PollCreated;
use App\Filament\Resources\Polls\PollResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $poll = parent::handleRecordCreation($data);

        event(new PollCreated($poll->loadMissing('building')));

        return $poll;
    }
}
