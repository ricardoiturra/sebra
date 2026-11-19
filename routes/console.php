<?php

use App\Http\Controllers\SphereColorController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('graph:notify', function (): void {
    $version = hash_file('sha256', base_path('graphify-out/graph.json'));

    if ($version !== false) {
        Redis::connection()->client()->executeRaw([
            'XADD', SphereColorController::GRAPH_STREAM, 'MAXLEN', '1', '*', 'graph', $version,
        ]);
    }
})->purpose('Notify open spheres that Graphify data changed');
