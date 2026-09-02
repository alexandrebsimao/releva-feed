<?php

namespace App\Console\Commands;

use App\Jobs\DispatchFeedFetchesJob;
use Illuminate\Console\Command;

class CollectFeeds extends Command
{
    protected $signature = 'feeds:collect';

    protected $description = 'Despacha a coleta assíncrona dos feeds ativos';

    public function handle(): int
    {
        DispatchFeedFetchesJob::dispatch();
        $this->info('Coleta assíncrona despachada para a fila feeds.');

        return self::SUCCESS;
    }
}
