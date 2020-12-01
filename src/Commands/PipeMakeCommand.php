<?php

namespace RoyVoetman\Repositories\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class RepositoryMakeCommand
 *
 * @package RoyVoetman\Repositories\Commands
 */
class PipeMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:pipe {--delete}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new pipe class';
    
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Pipe';
    
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('delete')) {
            return __DIR__.'/stubs/delete-pipe.stub';
        }
        
        return __DIR__.'/stubs/pipe.stub';
    }
    
    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Pipes';
    }
    
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Create pipe based on model.'],
        ];
    }
    
    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        
        return $stub;
    }
}
