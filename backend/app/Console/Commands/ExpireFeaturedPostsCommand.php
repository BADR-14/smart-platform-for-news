<?php

namespace App\Console\Commands;

use App\Services\Featured\FeaturedPostService;
use Illuminate\Console\Command;

class ExpireFeaturedPostsCommand extends Command
{
    protected $signature = 'featured:expire';

    protected $description = 'Mark featured posts whose duration has ended as expired';

    public function handle(FeaturedPostService $featuredPostService): int
    {
        $count = $featuredPostService->expireDue();

        $this->info("Expired {$count} featured post(s).");

        return self::SUCCESS;
    }
}
