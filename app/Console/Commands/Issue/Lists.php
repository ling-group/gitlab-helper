<?php

namespace App\Console\Commands\Issue;

use Illuminate\Console\Command;

class Lists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issue:lists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'issues lists';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }
}
