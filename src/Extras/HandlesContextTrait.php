<?php

namespace GBradley\ApiResource\Extras;

trait HandlesContextTrait
{

	protected $context;

    /**
     * Store context data.
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Get context data, optionally retriving a value from a nested array or object using "dot" notation.
     */
    public function getContext($key = null)
    {
        $data = $this->context;
        if ($data && $key) {
            $data = data_get($data, $key);
        }
        return $data;
    }

}