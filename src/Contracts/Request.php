<?php

namespace RoyVoetman\Extras\Contracts;

/**
 * Interface Request
 *
 * @package App\Interfaces
 */
interface Request
{
    /**
     * @return array
     */
    public function attributes(): array;
    
    /**
     * @return array
     */
    public function rules(): array;
    
    /**
     * @return string
     */
    public function getLanguageKey(): string;
}
