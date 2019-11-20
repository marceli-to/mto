<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ClearImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all generated images';

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
        $directories = Storage::allDirectories('public');
        $deletable   = [
            'public/media/images/processed/lg',
            'public/media/images/processed/sm',
            'public/media/images/processed/xs',
            'public/media/images/processed/preview',
            'public/media/images/processed/related',
        ];

        foreach($directories as $directory)
        {
            if (in_array($directory, $deletable))
            {
                Storage::deleteDirectory($directory);
            }
        }
    }
}
