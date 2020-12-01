<?php

namespace RoyVoetman\Repositories\Pipes;

use Illuminate\Support\Facades\DB;

class Transaction
{
    /**
     * @param $passable
     * @param  \Closure  $next
     * @param  int  $attempts
     *
     * @return mixed
     */
    public function handle($passable, \Closure $next, int $attempts)
    {
        return DB::transaction(fn () => $next($passable), $attempts);
    }
}
