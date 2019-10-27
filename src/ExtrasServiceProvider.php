<?php

namespace RoyVoetman\Extras;

use Illuminate\Support\ServiceProvider;
use RoyVoetman\Extras\Commands\RepositoryMakeCommand;

/**
 * Class RepositoryServiceProvider
 *
 * @package RoyVoetman\Repositories
 */
class ExtrasServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $commands = [
        RepositoryMakeCommand::class
    ];
    
    /**
     * Register bindings in the container.
     */
    public function register(){
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