<?php

namespace RoyVoetman\Repositories;

use Closure;
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
     * The pipes for specific by action.
     *
     * These pipes are automatically applied when specific actions occur.
     *
     * @var array
     */
    protected array $pipes = [
        'create' => [],
        'save' => [],
        'update' => [],
        'delete' => []
    ];
    
    /**
     * Multiple pipes can be grouped together for convenience.
     *
     * @var array
     */
    protected array $pipeGroups = [];
    
    /**
     * The repo's pipe stack.
     *
     * These pipes will be applied in the pipeline.
     *
     * @var array
     */
    private array $pipeStack = [];
    
    /**
     * These pipes groups are automatically added when specific action occurs.
     *
     * @var array
     */
    protected array $primitiveGroups = ['create', 'save', 'update', 'delete'];
    
    /**
     * Repository constructor.
     */
    protected function __construct()
    {
        if ($this instanceof UsesTransaction) {
            $this->transaction();
        }
    }
    
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
        $this->applyPrimitives(is_null($model) ? 'create' : 'update');
        
        return $this->pipeline($data, fn($data) => $this->performSave($data, $model));
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
        $this->applyPrimitives('delete');
        
        return $this->pipeline($model, fn($model) => $model->delete());
    }
    
    /**
     * Add pipes from a specific pipe group to the pipe stack.
     *
     * @param $group
     *
     * @return $this
     */
    public function withGroup($group): self
    {
        if (is_string($group)) {
            $group = Arr::wrap($group);
        }
        
        $groups = is_array($group) ? $group : func_get_args();
        
        foreach ($groups as $group) {
            $this->pipeStack = array_merge($this->pipeStack, $this->pipeGroups[$group]);
        }
        
        return $this;
    }
    
    /**
     * Apply the given pipe to the pipe stack.
     *
     * @param $pipe
     *
     * @return $this
     */
    public function with($pipe): self
    {
        $this->pipeStack[] = $pipe;
        
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
        array_unshift($this->pipeStack, Transaction::class);
        
        return $this;
    }
    
    /**
     * Retrieve all pipes that should be applied
     *
     * @return array
     */
    protected function pipeStack(): array
    {
        if ($this instanceof UsesTransaction) {
            $this->transaction();
        }
        
        return $this->pipeStack;
    }
    
    /**
     * Run the passable through the pipeline stack.
     *
     * @param $passable
     * @param $destination
     *
     * @return mixed
     */
    protected function pipeline($passable, Closure $destination)
    {
        $result = resolve(Pipeline::class)
            ->send($passable)
            ->through($this->pipeStack())
            ->then($destination);
        
        $this->pipeStack = [];
        
        return $result;
    }
    
    /**
     * Add the primitive pipes associated with the given action to the pipeline stack.
     *
     * @param $action
     *
     * @return $this
     */
    public function applyPrimitives(string $action): self
    {
        $actions = Arr::wrap($action);
        
        if (in_array($action, ['create', 'update'])) {
            $actions[] = ['save'];
        }
        
        foreach ($actions as $action) {
            // The Undefined index error will de neglected.
            // This removes the need to manually add all primitives when overriding the $pipes field
            if (!isset($this->pipes[$action])) {
                continue;
            }
            
            $this->pipeStack = array_merge($this->pipeStack, $this->pipes[$action]);
        }
        
        return $this;
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
