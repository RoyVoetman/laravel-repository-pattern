<?php

namespace RoyVoetman\Extras\Http;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request
 *
 * @package RoyVoetman\Extras\Http
 */
abstract class Request extends FormRequest implements \RoyVoetman\Extras\Contracts\Request
{
    /**
     * @return array
     */
    public function attributes(): array
    {
        $languageKey = $this->getLanguageKey();
        
        $attributes = [];
        
        foreach ($this->rules() as $column => $rules) {
            $column = str_replace('.*', '_all', $column);
            
            $attributes[$column] = __("{$languageKey}.{$column}");
        }
        
        return $attributes;
    }
    
    /**
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function getValidatorInstance(): Validator
    {
        if (method_exists($this, 'prepareRequestData')) {
            $this->prepareRequestData();
        }
        
        return parent::getValidatorInstance();
    }
}
