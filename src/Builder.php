<?php

namespace GBradley\ApiResource;

use GBradley\ApiResource\Resource;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Builder implements Responsable {

    protected $resourceable;
    protected $resource_class;
    protected $resource;
    protected $relations = [];
    protected $requested_relations = null;

    public function __construct($resourceable, string $resource_class)
    {
        $this->resourceable = $resourceable;
        $this->resource_class = $resource_class;
        $this->resource = $this->getResource();
    }

    /**
     * Return the resource being built.
     */
    public function getResource()
    {
        if (!$this->resource) {

            // Determine whether to create a model / collection resource based on the resourceable type.
            if ($this->resourceable instanceof Model) {
                $this->resource = new $this->resource_class($this->resourceable);
            } else {
                $this->resource = $this->resource_class::collection($this->resourceable);
            }
        }
        return $this->resource;
    }

    /**
     * Use a specific request to determine the requested relations.
     */
    public function withRequest($request, string $param = 'load')
    {
        return $this->withRequestedRelations($request->get($param, []));
    }

    /**
     * Specify the relations that are to be requested for the resource.
     */
    public function withRequestedRelations($relations)
    {
        if (!is_array($relations)) {
            $relations = func_get_args();
        }

        // Merge the provided list of requested relations. Note that we ensure any relation names submitted
        // in the request are in camelCase so they can be loaded correctly.
        $this->requested_relations = array_merge($this->requested_relations ?: [], array_map(function($relation) {
            return Str::camel($relation);
        }, $relations));

        return $this;
    }

    /**
     * Specify the relations to be loaded for the resource.
     */
    public function withRelations($relations)
    {
        if (!is_array($relations)) {
            $relations = func_get_args();
        }

        // Merge the provided list of relations.
        $this->relations = array_merge($this->relations, $relations);

        return $this;
    }

    /**
     * Specify the relations that may be requested for the resource.
     */
    public function withOptionalRelations($optional_relations)
    {
        if (!is_array($optional_relations)) {
            $optional_relations = func_get_args();
        }

        // If the requested relations haven't been retrieved, get them now.
        if ($this->requested_relations === null) {
            $this->withRequest(app('request'));
        }

        // Unnset the optional & requested relations, and find the relations common to both.
        $allowed = array_intersect(
            $this->unnestRelations($optional_relations),
            $this->unnestRelations($this->requested_relations)
        );

        // Merge the allowed relations into the main relations array.
        return $this->withRelations($allowed);
    }

    /**
     * Expand an array of potentially nested relations into all variants.
     */
    protected function unnestRelations(array $relations) : array
    {
        return array_unique(Arr::collapse(array_map(function($relation) {
            return $this->unnestRelation($relation);
        }, $relations)));
    }

    /**
     * Return a relation as an array of possible nested variants.
     */
    protected function unnestRelation(string $relation) : array
    {
        $str = '';
        $relations = [];
        foreach (explode('.', $relation) as $part) {
            $str .= $part;
            $relations[] = $str;
            $str .= '.';
        }
        return $relations;
    }

    /**
     * Prepare the resource and convert to a response.
     */
    public function toResponse($request)
    {
        return $this->prepare()
            ->getResource()
            ->toResponse($request);
    }

    /**
     * Prepare the resource for use.
     */
    protected function prepare()
    {

        // Remove any duplicated relations
        $relations = array_unique($this->relations);

        // Load any missing relations.
        $this->loadMissingRelations($relations);

        // Split the potentially nested relations into arrays and pass to the resource.
        $this->resource->setRelations(array_map(function($relation) {
            return explode('.', $relation);
        }, $relations));

        return $this;
    }

    /**
     * Load and missing relations.
     */
    protected function loadMissingRelations(array $relations)
    {

        $loadable = $this->resourceable;

        // Determine if the reource is a paginator.
        if ($loadable instanceof AbstractPaginator) {

            // Get the paginator's underlying collection and find the first model, if it exists.
            $collection = $loadable->getCollection();
            if ($model = $collection->first()) {

                // Convert the paginator's (support) collection into a new (eloquent) collection. Note
                // that we don't need to modify the paginator in any way, as all we want to do is 
                // obtain a reference to its models in one loadable object in preparation for eager loading.
                $loadable = $model->newCollection($collection->all());
            } else {
                $loadable = null;
            }
        }

        // Perform the eager load if required.
        if ($loadable) {
            $loadable->loadMissing($relations);
        }
    }

}