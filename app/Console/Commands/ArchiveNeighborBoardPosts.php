<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\NeighborBoardService;
use Illuminate\Console\Command;

final class ArchiveNeighborBoardPosts extends Command
{
    protected $signature = 'neighbor-board:archive-posts';

    protected $description = 'Archive old active and resolved neighbor board posts based on retention rules.';

    public function __construct(private readonly NeighborBoardService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->service->autoArchiveExpired();

        $this->info(sprintf(
            'Archived %d active and %d resolved posts.',
            $result['active_archived'],
            $result['resolved_archived'],
        ));

        return self::SUCCESS;
    }
}
