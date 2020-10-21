<?php

namespace RoyVoetman\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use RoyVoetman\Repositories\Interfaces\UsesTransaction;
use RoyVoetman\Repositories\Pipes\Transaction;

/**
 * Class Repository
 *
 * @package RoyVoetman\Repositories
 */
abstract class Repository
{
    /**
     * The fully qualified class name of the model associated to this repository
     *
     * @var string
     */
    private string $model;
    
    /**
     * The repo's global pipe stack.
     *
     * These pipes are run during every action to the database.
     *
     * @var array
     */
    protected array $pipes = [];
    
    /**
     * Multiple pipes can be grouped together for convenience.
     *
     * @var array
     */
    protected array $pipeGroups = [
        'create' => [],
        'save' => [],
        'update' => [],
        'delete' => []
    ];
    
    /**
     * These pipes groups are automatically added when specific action occurs.
     *
     * @var array
     */
    protected array $primitiveGroups = ['create', 'save', 'update', 'delete'];
    
    /**
     * Save the model to the database via a pipeline.
     *
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function save(array $data, ?Model $model = null): ?Model
    {
        $this->withPipeGroup([
            'save',
            !is_null($model) ? 'update' : 'create'
        ]);
        
        return resolve(Pipeline::class)
            ->send($data)
            ->through($this->pipes)
            ->then(fn($data) => $this->performSave($data, $model));
    }
    
    /**
     * Delete the model from the database via a pipeline.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return bool|null
     */
    public function delete(Model $model): ?bool
    {
        $this->withPipeGroup('delete');
        
        return resolve(Pipeline::class)
            ->send($model)
            ->through($this->pipes)
            ->then(fn($model) => $model->delete());
    }
    
    /**
     * Add pipes from a specific pipe group to the pipe stack.
     *
     * @param $group
     *
     * @return $this
     */
    public function withPipeGroup($group): self
    {
        if (is_string($group)) {
            $group = Arr::wrap($group);
        }
        
        $groups = is_array($group) ? $group : func_get_args();
        
        foreach ($groups as $group) {
            // The Undefined index error will de neglected when a primitive pipe group is not defined
            // This removes the need to manually add all primitive groups when overriding $pipeGroups
            if (!array_key_exists($group, $this->pipeGroups) && in_array($group, $this->primitiveGroups)) {
                continue;
            }
            
            $this->pipes = array_merge($this->pipes, $this->pipeGroups[$group]);
        }
        
        return $this;
    }
    
    /**
     * Add specific pipe to the pipe group.
     *
     * @param $pipe
     *
     * @return $this
     */
    public function withPipe($pipe): self
    {
        $this->pipes[] = $pipe;
        
        return $this;
    }
    
    /**
     * Prepend the transaction pipe to the pipe stack.
     *
     * This will result in running all queries inside a transaction.
     *
     * @return $this
     */
    public function transaction(): self
    {
        array_unshift($this->pipes, Transaction::class);
        
        return $this;
    }
    
    /**
     * Retrieve all pipes
     *
     * @return array
     */
    protected function pipes(): array
    {
        if ($this instanceof UsesTransaction) {
            $this->transaction();
        }
        
        return $this->pipes;
    }
    
    /**
     * Perform a model save operation.
     *
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function performSave(array $data, ?Model $model = null): Model
    {
        $model ??= (new $this->model)->newInstance();
        
        $model->fill($data)->save();
        
        return $model;
    }
}
