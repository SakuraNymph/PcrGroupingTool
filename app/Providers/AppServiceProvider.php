<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // DB::listen(function ($query) {
        //     Log::info('SQL: ' . $query->sql);
        //     Log::info('Bindings: ' . json_encode($query->bindings));
        //     Log::info('Time: ' . $query->time . ' ms');
        // });

        // 为所有 JsonResponse 默认开启不转义中文
        Response::macro('jsonUnicode', function ($data) {
            return Response::json($data)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        });
    }
}
