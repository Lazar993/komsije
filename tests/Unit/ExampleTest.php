<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_ticket_status_labels_are_human_readable(): void
    {
        $this->assertSame('In Progress', TicketStatus::InProgress->label());
    }
}
