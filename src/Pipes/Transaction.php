<?php

namespace RoyVoetman\Repositories\Pipes;

use Illuminate\Support\Facades\DB;

class Transaction
{
    /**
     * @var int
     */
    protected int $attempts;

    /**
     * Transaction constructor.
     *
     * @param  int  $attempts
     */
    public function __construct(int $attempts = 1)
    {
        $this->attempts = $attempts;
    }

    /**
     * @param $data
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle($data, \Closure $next)
    {
        return DB::transaction(fn () => $next($data), $this->attempts);
    }
}
