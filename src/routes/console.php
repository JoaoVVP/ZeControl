<?php

use App\Jobs\ProcessarEventosZeJob;
use App\Models\Loja;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Loja::whereNotNull('ze_client_id')->each(function ($loja) {
        ProcessarEventosZeJob::dispatch($loja->id);
    });
})->everyThirtySeconds();