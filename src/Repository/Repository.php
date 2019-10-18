<?php

namespace RoyVoetman\Extras\Repository;

use \Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class Repository
 *
 * @package App\Repositories
 */
abstract class Repository
{
    /**
     * @var Model
     */
    protected $model;
    
    /**
     * @var string
     */
    protected $transactionErrorMsg;
    
    /**
     * @var string
     */
    protected $saveErrorMsg;
    
    /**
     * @var string
     */
    protected $deleteErrorMsg;
    
    /**
     * Repository constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function __construct(Model $model)
    {
        $this->model = $model;
        
        $this->transactionErrorMsg = __('alerts.could not start database transaction');
        $this->saveErrorMsg = __('alerts.database error');
        $this->deleteErrorMsg = __('alerts.could not delete');
    }
    
    /**
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function save(array $data, Model $model = null): ?Model
    {
        $status = $this->transaction(function () use ($data, $model) {
            $this->handleSave($data, $model);
        }, $this->saveErrorMsg);
        
        return $status ? $this->model : null;
    }
    
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return bool
     */
    public function delete(Model $model): bool
    {
        return $this->transaction(function () use ($model) {
            $this->handleDelete($model);
        }, $this->deleteErrorMsg);
    }
    
    /**
     * @param  \Closure  $handle
     * @param  null  $fail
     *
     * @return bool
     */
    protected function transaction(\Closure $handle, $fail = null): bool
    {
        try {
            DB::beginTransaction();
            
            try {
                $handle();
                
                DB::commit();
                
                return true;
            } catch (Exception $e) {
                DB::rollBack();
                
                if (is_callable($fail)) {
                    $fail();
                }
                
                if (is_string($fail)) {
                    session()->flash('warning', $fail);
                }
            }
        } catch (Exception $e) {
            session()->flash('warning', $this->transactionErrorMsg);
        }
        
        return false;
    }
    
    /**
     * @param  array  $data
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleSave(array $data, Model $model = null): Model
    {
        $this->model = $model ?? $this->model->newInstance();
    
        $this->model->fill($data)
            ->save();
    
        return $this->model;
    }
    
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @throws \Exception
     */
    protected function handleDelete(Model $model)
    {
        $model->delete();
    }
}
