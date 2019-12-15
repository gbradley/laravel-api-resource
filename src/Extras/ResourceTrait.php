<?php

/**
 * This trait can be used in resources to customise the wrap name.
 */

namespace GBradley\ApiResource\Extras;

use Illuminate\Support\Str;

trait ResourceTrait
{
	
	/**
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    protected function wrapper()
    {
        if ($collects = $this->resource->collects) {
            $wrap = $collects::$wrapCollection;
        } else {
            $wrap = parent::wrapper();
        }
        return $wrap;
    }

}
