<?php

namespace App\Services\Forus\Record;

use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use App\Services\Forus\Record\Repositories\RecordIpfsRepo;
use App\Services\Forus\Record\Repositories\RecordRepo;
use Illuminate\Support\ServiceProvider;

class RecordServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (env('SERVICE_RECORDS_URL')) {
            $this->app->bind(IRecordRepo::class, function() {
                return new RecordIpfsRepo(env('SERVICE_RECORDS_URL'));
            });
        } else {
            $this->app->bind(IRecordRepo::class, RecordRepo::class);
        }


        $this->app->singleton('forus.services.record', function () {
            return app(IRecordRepo::class);
        });
    }
}