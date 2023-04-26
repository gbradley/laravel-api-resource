<?php

namespace GBradley\ApiResource;

use GBradley\ApiResource\Extras\HandlesContextTrait;
use GBradley\ApiResource\Extras\HandlesNestedRelationsTrait;
use GBradley\ApiResource\ResourceResponse;
use GBradley\ApiResource\PaginatedResourceResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    use HandlesContextTrait;
    use HandlesNestedRelationsTrait;

    /**
     * @override
     */
    public function toArray($request)
    {
        // Pass the relations and context down to each item in the collection.
        $this->collection->each(function ($item) {
            $item->setRelations($this->relations);
            $item->setContext($this->context);
        });

        // Retain the key preservation state.
        if ($item = $this->collection->first()) {
            $this->preserveKeys = $item->preserveKeys;
        }

        return parent::toArray($request);
    }

    /**
     * @override
     */
    public function toResponse($request)
    {
        return $this->resource instanceof AbstractPaginator
            ? (new PaginatedResourceResponse($this))->toResponse($request)
            : (new ResourceResponse($this))->toResponse($request);
    }
}
