<?php

namespace RoyVoetman\Repositories\Pipes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EncryptPassword
{
    /**
     * @var string
     */
    protected string $passwordKey = 'password';

    /**
     * @var string
     */
    protected string $confirmationKey = 'password_confirm';

    /**
     * @param $data
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($data, \Closure $next): Model
    {
        if(Arr::has($data, $this->passwordKey)) {
            Arr::forget($data, $this->confirmationKey);

            $data[$this->passwordKey] = bcrypt($data[$this->passwordKey]);
        }

        return $next($data);
    }
}
