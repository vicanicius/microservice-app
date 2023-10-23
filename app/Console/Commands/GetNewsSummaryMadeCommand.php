<?php

namespace App\Console\Commands;

use App\Services\NewsApi\Contracts\NewsServiceContract;
use Illuminate\Console\Command;

class GetNewsSummaryMadeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-news-summary-made';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(NewsServiceContract $service): void
    {
        $service->getSummaryNews();
    }
}
