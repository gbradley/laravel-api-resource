<?php

namespace GBradley\ApiResource;

use GBradley\ApiResource\Builder;
use GBradley\ApiResource\AnonymousResourceCollection;
use GBradley\ApiResource\HandlesNestedRelationsTrait;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class Resource extends JsonResource
{

	use HandlesNestedRelationsTrait;

	/**
	 * Create a new builder instance for the item to be converted into the called resource.
	 */
	public static function build($resourceable) : Builder
	{
		return new Builder($resourceable, get_called_class());
	}

	/**
	 * @override - use our custom collection resource.
	 */
	public static function collection($resource)
	{
		return new AnonymousResourceCollection($resource, static::class);
	}

	/**
	 * Return a representation of the resource from an array of attrbutes.
	 */
	protected function mergeAttributes($attributes)
	{

		if (!is_array($attributes)) {
			$attributes = func_get_args();
		}

		$data = [];
		$casts = $this->getCasts();
		foreach ($attributes as $attribute) {

			// Use call-forwarding to access the underling object's attribute value.
			$value = $this->{$attribute};

			// If the value is cast to date / datetime and a format is provided, apply the format to the value.
			if (($cast = $casts[$attribute] ?? null) && preg_match('/^date(?!time)?:(.+)/', $cast, $match)) {
				$value = $value->format($match[1]);
			}

			$data[$attribute] = $value;
		}

		return $this->mergeWhen(true, $data);
	}

	/**
	 * Return a merge value that ensures the relation is loaded when & only when
	 * it has been explicitly allowed by the controller.
	 */
	protected function mergeWhenExplicitlyLoaded($relations)
	{

		if (!is_array($relations)) {
			$relations = func_get_args();
		}

		$mergeable = [];
		$allowed = $this->getTopLevelRelations();

		foreach ($relations as $relation => $resource_class) {
			if (is_numeric($relation)) {
				$relation = $resource_class;
				$resource_class = null;
			}

			// Generate the "real" relation name.
			$name = Str::camel($relation);

			if (in_array($name, $allowed)) {
				$mergeable[$relation] = $this->wrapWhenLoadedWith($name, $resource_class);
			}
		}
		return $this->mergeWhen(true, $mergeable);
	}

	/**
	 * Create a 'whenLoaded' merge value for the named relation, and wrap it in the appropriate resource if provided.
	 */
	protected function wrapWhenLoadedWith(string $relation, ?string $resource)
	{

		
		$when = $this->whenLoaded($relation);

		// If a resource is provided, determine how to use it.
		if ($resource) {
			if ($this->isSinglularRelation($relation)) {
				$when = new $resource($when);
			} else {
				$when = $resource::collection($when);
			}
			$when->setRelations($this->getNestedRelations());
		}

		return $when;
	}

	/**
	 * Determine if the named relation will return a single instance or a collection.
	 */
	protected function isSinglularRelation(string $relation) : bool
	{
		$instance = $this->{$relation}();
		return $instance instanceof BelongsTo
			|| $instance instanceof HasOne
			|| $instance instanceof HasOneThrough
			|| $instance instanceof MorphOne
			|| $instance instanceof MorphTo;
	}

}