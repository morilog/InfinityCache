# InfinityCache
A Laravel package Eloquent cache. InfinityCache provides infinity and forever cache for queries results unitl model changed. If model has been created or deleted or updated, model queries caches will be flushed.

### Requirements
This package works only with ***taggable cache storages*** and drivers such as `memcached` or `Redis` and other storages that extended from `Illuminate\Cache\TaggedCache`.

### Installation
For install this package just run composer with this command:
~~~ bash
composer require morilog/infinity-cache
~~~
### Usage
For using InfinityCache, your eloquent model must be extends `Morilog\InfinityCache\Model` 

example:
~~~ php
<?php
namespace App\Models;

use Morilog\InfinityCache\Model as InifinityCacheModel;

class Post extends InifinityCacheModel 
{
  ...
}
~~~
