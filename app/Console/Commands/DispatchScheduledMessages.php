<?php

namespace App\Console\Commands;

use App\Services\ScheduledMessageDispatcher;
use Illuminate\Console\Command;

class DispatchScheduledMessages extends Command
{
    protected $signature = 'messages:dispatch {--limit=25 : Maximum due messages inspected per run}';

    protected $description = 'Send due WhatsApp messages using per-device throttling and safety limits';

    public function handle(ScheduledMessageDispatcher $dispatcher): int
    {
        $result = $dispatcher->dispatchDue(max(1, (int) $this->option('limit')));
        $this->info(collect($result)->map(fn ($count, $key) => "{$key}={$count}")->implode(' '));

        return self::SUCCESS;
    }
}
