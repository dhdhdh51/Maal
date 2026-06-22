<?php

namespace App\Console\Commands;

use App\Services\Payments\AccessGrantService;
use Illuminate\Console\Command;

class ExpireAccess extends Command
{
    protected $signature = 'maal:expire-access';

    protected $description = 'Expire category access grants whose validity window has elapsed.';

    public function handle(AccessGrantService $access): int
    {
        $count = $access->expireDue();
        $this->info("Expired {$count} access grant(s).");

        return self::SUCCESS;
    }
}
