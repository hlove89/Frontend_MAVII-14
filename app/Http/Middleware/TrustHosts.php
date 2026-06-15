<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    public function hosts(): array
{
    return [
        'mavii.my.id',
        'www.mavii.my.id',
    ];
}
}
