<?php

namespace GBradley\ApiResource;

use GBradley\ApiResource\HandlesNestedRelationsTrait;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{

	use HandlesNestedRelationsTrait;

	/**
	 * @override
	 */
	public function toArray($request)
    {

    	// Pass the relations down to each item in the collection.
    	$this->collection->each(function($item) {
    		$item->setRelations($this->relations);
    	});
        return parent::toArray($request);
    }

}