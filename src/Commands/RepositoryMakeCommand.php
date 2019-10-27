<?php

namespace RoyVoetman\Extras\Commands;

use Illuminate\Console\GeneratorCommand;

/**
 * Class CreateRepository
 *
 * @package RoyVoetman\Extras\Commands
 */
class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:repository';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';
    
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';
    
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/repository.stub';
    }
    
    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Repositories';
    }
}