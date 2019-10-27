<?php

namespace RoyVoetman\Extras\Http\Traits;

use RoyVoetman\Extras\Contracts\ViewPrefix;
use Illuminate\Contracts\View\View;

/**
 * Trait CreatesViews
 *
 * @package RoyVoetman\Extras\Http\Traits
 */
trait CreatesViews
{
    /**
     * @param  string  $view
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view(string $view = null): View
    {
        $view = is_null($view) ? $this->getViewByFunctionName() : $view;
        
        if($this instanceof ViewPrefix) {
            return view($this->viewPrefix() . '.' . $view);
        }
        
        return view($view);
    }
    
    /**
     * @return string
     */
    private function getViewByFunctionName(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        return $trace[2]['function'];
    }
}
