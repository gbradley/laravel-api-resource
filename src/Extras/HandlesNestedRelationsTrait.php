<?php

/**
 * This trait can be used to set & provide relation names to both models and collections.
 */

namespace GBradley\ApiResource\Extras;

trait HandlesNestedRelationsTrait
{

    protected $relations = [];

    /**
     * Set the relations array.
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;
    }

    /**
     * Return the top-level relations.
     */
    public function getTopLevelRelations() : array
    {
        return array_map(function($relation) {
            return array_shift($relation);
        }, $this->relations);
    }

    /**
     * Return the lower-level relations.
     */
    public function getNestedRelations() : array
    {
        return array_filter(array_map(function($relation) {
            array_shift($relation);
            return $relation;
        }, $this->relations));
    }

}