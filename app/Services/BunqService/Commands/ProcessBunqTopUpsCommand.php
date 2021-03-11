<?php

namespace App\Services\BunqService\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

/**
 * Class ProcessBunqTopUpsCommand
 * @package App\Services\BunqService\Commands
 */
class ProcessBunqTopUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.bunq:top_up_process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process bunq top ups queue.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        try {
            BunqService::processTopUps();
        } catch (\Exception $e) {
            logger()->debug(sprintf("Failed to process bunq top-ups: %s", $e->getMessage()));
        }
    }
}
