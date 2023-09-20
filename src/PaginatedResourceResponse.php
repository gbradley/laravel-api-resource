<?php

/**
 * Use the resource trait to ensure paginated responses are wrapped.
 */

namespace GBradley\ApiResource;

use GBradley\ApiResource\Extras\ResourceTrait;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse as BasePaginatedResourceResponse;

class PaginatedResourceResponse extends BasePaginatedResourceResponse
{
    use ResourceTrait;

    public function toPaginator($request): array
    {
        return $this->wrap(
            $this->resource->resolve($request),
            array_merge_recursive(
                $this->paginationInformation($request),
                $this->resource->with($request),
                $this->resource->additional
            )
        );
    }
}
