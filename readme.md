This package expands on Laravel's base classes to provide a more succinct yet powerful way to control your API resources.

## Goals

- Less code repetition; API resources take less time to write and maintain
- Improved relation handling including loading on-demand
- More control over data wrapping
- unopinionated; won't affect your existing API by default

## Requirements

- Laravel 5.8+

## Installation

	$ composer require gbradley/laravel-api-resource
	
## Usage

- [Extending the new resource class](#extending-the-new-resource-class)
- [Quicker attribute definitions](#quicker-attribute-definitions)
- [Relation handling](#relation-handling)
- [Contextual data](#contextual-data)
- [Data wrapping](#data-wrapping)

### Extending the new resource class

In your resource, instead of extending Laravel's `Response`, change this to extend `GBradley\ApiResource\Resource` instead:

	namespace App\Http\Resources;
	
	use GBradley\ApiResource\Resource;
	
	class PostResource extends Resource
	{
		...
	}

That's it! You can now use this resource as you would any normal resource. A main goal of this package is not to change resources' existing behaviour.

### Quicker attribute definitions

In our example resource, lets define some attributes. Normally you would have to do this individually, which can be slow to write. Instead, you can now use `mergeAtrributes()` to quickly define the attributes you wish to use from your underlying model. This reduces something like:

	class PostResource extends Resource
	{

		public function toArray($request)
		{
			return [
				'id'		=> $this->id,
				'title'		=> $this->title,
				...
			];
		}
	
to this:

	class PostResource extends Resource
	{

		public function toArray($request)
		{
			return [
				$this->mergeAttributes('id', 'title', ...),
			];
		}
	
This has the additional benefit of retaning date formats in casted `date` or `datetime` attributes. Normally you would have to specify the format again in  your resource, but this will now be handled for you.

### Relation handling

Laravel's resources have two strategies for adding relations:

**Direct loading** - this always loads relations, often resulting in non-eager loaded queries being executed to generate data which the front-end may not need.

**When loaded** - this returns relations when they are already loaded, expecting the controller to handle loading. However, you may also have business logic that loads relations (such as summing up values), meaning your response structure may be affected by internal side-effects.

Instead, this package allows controllers to explicitly define which relations the resource *can* expose, with the resource determining which relations it *will* expose. These can either be *required* relations, which will always be exposed, or *optional* relations, which will be exposed if specified in the current request. The result is a flexible system which also utilises eager-loading.

To start, open your controller. Instead of instantiating a resource, or using the `collection()` method, use the static `build` method which can accept a model, a collection or a paginator instance:

	return PostResource::build($model);

This exposes a fluid interface for specifying the relations. To specify relations which should always be exposed, pass an array of names to `withRelations()`:

	return PostResource::build($model)
		->withRelations('blog', 'author');

Use `withOptionalRelations()` in the same manner to define relations which will only be exposed if found in the `load` parameter of the current request:
	
	return PostResource::build($model)
		->withRelations('blog', 'author')
		->withOptionalRelations('comments.author');

As you can see from the above example, these methods also accept nested relations.

Now the controller has defined what it allows the resource to expose, you can configure the resource to do so using `mergeWhenExplicitlyLoaded()`. This method accepts the array of relation names that the resource can expose:

	class PostResource extends Resource
	{
	
		public function toArray($request)
		{
			return [
				$this->mergeAttributes('id', 'title'),
				$this->mergeWhenExplicitlyLoaded([
					'blog', 'author', 'comments'
				]),
			];
		}
	
When passing items sequentially, they will be exposed using the related object's `toArray` method. If you would like to transform them into other resources, pass the relation as the key and the desired resource class as the value. These techniques can be combined:

	class PostResource extends Resource
	{

		public function toArray($request)
		{
			return [
				$this->mergeAttributes('id', 'title'),
				$this->mergeWhenExplicitlyLoaded([
					'blog',
					'author',
					'comments' => CommentResource::class,
				]),
			];
		}

In this case, we can now also define `CommentResource` to expose its `author` relation when explicitly loaded.

	class CommentResource extends Resource
	{
	
		public function toArray($request)
		{
			return [
				$this->mergeAttributes('id', 'content'),
				$this->mergeWhenExplicitlyLoaded([
					'author',
				]),
			];
		}
	
	

This results in a request like this:

	`GET /post/1?load[]=comments.author`
	
returning something like this:

	{
		'id' : 1,
		'name' : 'Blog post 1',
		'blog'	: {
			'id' => 123,
			'title' => 'My Blog',
		},
		'author' : {
			'id' : 456,
			'name' : 'Graham',
		},
		'comments' : [
			{
				'id' : 789,
				'content' : 'Nice post!',
				'author' : {
					'id' : 987,
					'name' : 'Taylor Otwell'
				}
			}
		]
	}
	
### Contextual data

Sometimes you may want to modify your resource's transformation based on information that isn't found in the request or the model being transformed.

To make arbitary data available to your resource, call `withContext()` on the resource builder returned from `build()`:

	$context = [
		'foo' => [
			'bar' => 1
		]
	];

	return PostResource::build($model)
		->withRelations('blog')
		->withContext($context);

Your context data can be accessed inside the resource with `getContext()`. You can also use dot notation to retrive a subset of the data:

	$data = $this->getContext('foo.bar');
	
You may use `mergeContext()` to merge all or part of the context into your representation:

	class PostResource extends Resource
	{
	
		public function toArray($request)
		{
			return [
				$this->mergeAttributes('id', 'title'),
				$this->mergeWhenExplicitlyLoaded([
					'blog' => BlogResource::class,
				]),
				$this->mergeContext('foo.bar')
			];
		}

Any context data provided to a resource will be passed down to any other resources loaded via relations. In the above example, the context data will be available on the instances of `BlogResource`.


### Data wrapping
	
Laravel's resources allow you to disable data wrapping, however this does so for both models and collections. Although the security risks of returning top-level JSON arrays appear to have been resolved in all browers, some may prefer to avoid wrapping single models but retain wrapping for collections.

Do to this, call `withoutWrapping()` on the new Resource class, followed by `wrapCollection()`:

	public function boot()
	{
		Resource::withoutWrapping();
		Resource::wrapCollection('data');
	}