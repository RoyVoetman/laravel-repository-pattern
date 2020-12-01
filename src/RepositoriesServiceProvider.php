<?php

namespace RoyVoetman\Repositories;

use Illuminate\Support\ServiceProvider;
use RoyVoetman\Repositories\Commands\PipeMakeCommand;
use RoyVoetman\Repositories\Commands\RepositoryMakeCommand;

/**
 * Class RepositoriesServiceProvider
 *
 * @package RoyVoetman\Repositories
 */
class RepositoriesServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected array $commands = [
        RepositoryMakeCommand::class,
        PipeMakeCommand::class,
    ];
    
    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->commands($this->commands);
    }
    
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        //
    }
}
