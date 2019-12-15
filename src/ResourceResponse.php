<?php

/**
 * Use the resource trait to ensure responses are wrapped contextually.
 */

namespace GBradley\ApiResource;

use GBradley\ApiResource\Extras\ResourceTrait;
use Illuminate\Http\Resources\Json\ResourceResponse as BaseResourceResponse;

class ResourceResponse extends BaseResourceResponse {

	use ResourceTrait;
	
}
