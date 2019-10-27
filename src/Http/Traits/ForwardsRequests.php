<?php

namespace RoyVoetman\Extras\Http\Traits;

use RoyVoetman\Extras\Contracts\RoutePrefix;
use Illuminate\Http\RedirectResponse;

/**
 * Trait ForwardsRequests
 *
 * @package RoyVoetman\Extras\Http\Traits
 */
trait ForwardsRequests
{
    /**
     * @param  string  $route
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(string $route = 'index'): RedirectResponse
    {
        if($this instanceof RoutePrefix) {
            return redirect()->route( $this->routePrefix() . '.' . $route);
        }
        
        return redirect()->route($route);
    }
}
