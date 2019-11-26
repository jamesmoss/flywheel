# Flywheel

[![Build Status](https://travis-ci.org/jamesmoss/flywheel.png?branch=master)](https://travis-ci.org/jamesmoss/flywheel) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jamesmoss/flywheel/badges/quality-score.png?s=a446767a189b05b7eea08f639a3843dd85419f55)](https://scrutinizer-ci.com/g/jamesmoss/flywheel/) [![Code Coverage](https://scrutinizer-ci.com/g/jamesmoss/flywheel/badges/coverage.png?s=98540d0552a411c4e2fbcc5405e09e7be886e370)](https://scrutinizer-ci.com/g/jamesmoss/flywheel/)

A flat-file, serverless, document database for PHP that can store data in JSON, YAML or Markdown formats.

Often MySQL can be overkill for a small site or blog installation. Although it's present by as standard
on many hosting packages it still requires several manual steps including configuration, user and databases
creation etc.

Additionally, content stored in MySQL databases is impossible (or at least very
difficult) to track using version control software. This makes sharing a site or app
between a team difficult, requiring everybody to have access to a master database or
their own copy. There's also complications when apps are setup on staging servers
and changes that users make must be reflected in a developer's local copy.
You've probably come up against this issue in the past and it's all a bit of a mess.

Flywheel hopes to enable a new breed of PHP apps and libraries by giving developers access
to a datastore that acts in a similar way to a NoSQL database but has zero
external dependencies. Documents (essentially associative arrays), can be saved and retrieved,
sorted and limited.

Currently Flywheel is in heavy development and is not production ready yet. You might find
that documents created from one version of Flywheel can't be loaded by another right now.
As we get closer and closer to a v1 this is less likely to happen.

Flywheel is opinionated software. The following is assumed:

- Simple data structures are best.
- You're not going to be storing tens of thousands of documents.

## Requirements

- PHP 5.3+
- Composer

**Optionally**

- APC / APCu - caches documents and queries in memory for huge performance gains.

## Installation

Use [Composer](http://getcomposer.org/) to install the flywheel package. Package details [can be found on Packagist.org](https://packagist.org/packages/jamesmoss/flywheel).

Run `composer require jamesmoss/flywheel` in your project directory to install the Flywheel package.

## Use

```php
$config = new \JamesMoss\Flywheel\Config('path/to/writable/directory');

// The repository is responsible for storing, updating and deleting documents.
$repo = new \JamesMoss\Flywheel\Repository('posts', $config);

// Storing a new document
$post = new \JamesMoss\Flywheel\Document(array(
    'title'     => 'An introduction to Flywheel',
    'dateAdded' => new \DateTime('2013-10-10'),
    'body'      => 'A lightweight, flat-file, document database for PHP...',
    'wordCount' => 7,
    'author'    => 'James',
    'published' => true,
    'translations' => array(
    	'de' => 'Eine Einführung in Flywheel',
    	'it' => 'Una introduzione a Flywheel',
    ),
));

echo $post->title; // An introduction to Flywheel
echo $post->wordCount; // 7

$id = $repo->store($post);

// A unique ID is automatically generated for you if you don't specify your own when storing.
// The generated ID consists of upper/lowercase letters and numbers so is URL safe.
echo $id; // Czk6SPu4X
echo $post->getId(); // Czk6SPu4X

// If you set your own then it cannot contain the following characters: / ? * : ; { } \ or newline
$post->setId('a-review-of-2013');

// Retrieving documents
$posts = $repo->query()
    ->where('dateAdded', '>', new \DateTime('2013-11-18'))
    ->andWhere('published', '==', true)
    ->orderBy('wordCount DESC')
    ->limit(10, 5)
    ->execute();

echo count($posts); // 5 the number of documents returned in this result
echo $posts->total() // 33 the number of documents if no limit was applied. Useful for pagination.

foreach($posts as $post) {
    echo $post->title;
}

// Pull one document out of the repo by ID
$post = $repo->findById('a-quick-guide-to-flywheel');

// Updating documents
$post->title = 'How to update documents';

// Updates the document (only if it already exists)
$repo->update($post);


// Deleting documents - you can pass a document or it's ID.
$repo->delete($post);
// or you can do the following
$repo->delete('Czk6SPu4X');

```

## Querying

You can filter down the number of documents returned by using the `where`, `andWhere`
and `orWhere` methods on a query.

```php
// Find posts with more than 100 words written by James
$posts = $repo->query()
    ->where('wordCount', '>', 100)
    ->andWhere('author', '==', 'James')
    ->execute();

// The special __id field name can be used to query by the document's ID.
// Find all posts where the ID is either 1 or 7 or 8.
$posts = $repo->query()
    ->where('__id', '==', 1)
    ->orWhere('__id', '==', 7)
    ->orWhere('__id', '==', 8)
    ->execute();

// A neater, alternative way of doing the above query
$posts = $repo->query()
    ->where('__id', 'IN', array(1, 7, 8))
    ->execute();

// You can query by sub keys within a document too. The following finds all
// documents which have a German translation
$posts = $repo->query()
    ->where('translations.de', '!=', false)
    ->execute();
```

You can pass in an anonymous function to the `where`, `andWhere`
and `orWhere` methods to group predicates together. The anonymous function takes
one parameter which is an instance of `JamesMoss\Flywheel\Predicate` and has
the same methods. You can nest as many times you as like.

```php
$posts = $repo->query()
    ->where('wordCount', '>', 100)
    ->andWhere(function($query) {
    	$query->where('author', '==', 'Hugo')
    	$query->orWhere('dateAdded', '>', new \DateTime('2014-05-04'))
    })
    ->execute();
```

**Important** Traditional logical operator precedence is not implemented yet.
If you have a mix of `AND` and `OR`s then you might not see the behaviour you expect.
Currently `AND` predicates are processed before `OR`, regardless of the order
they were defined in. Always use anonymous function to group your predicates
together explictly if you have a mix of `AND` and `OR`s.

The list of available comparison operators are:

- `==` Equality
- `===` Strict equality
- `!=` Not equals
- `!==` Strict not equals
- `>` Greater than
- `>=`  Greater than or equal to
- `<` Less than
- `<=` Less than or equal to
- `IN` Check if value is in the set. Equality checks are not strict.

It's possible to order the returned result using `orderBy`.

```php
// A simple example
$posts = $repo->query()
    ->orderBy('wordCount ASC')
    ->execute();

// You can use sub keys too
$posts = $repo->query()
    ->orderBy('translations.de DESC')
    ->execute();
    
// It's possible to sort on multiple fields by passing in an array to orderBy
$posts = $repo->query()
    ->orderBy(['name ASC', 'age DESC'])
    ->execute();

// Use the special __id field name to sort by the document's ID
$posts = $repo->query()
    ->orderBy('__id')
    ->execute();
```

You can limit how many documents get returned from the repo using `limit`. Offsets
are also supported much like a traditional database.

```php
// Get the last 5 blog posts
$posts = $repo->query()
	->where('published', '==', 'true')
    ->orderBy('dateAdded DESC')
    ->limit(5)
    ->execute();

 // Get 25 blog posts offset by 100 from the start.
 $posts = $repo->query()
 	->where('published', '==', 'true')
    ->orderBy('dateAdded DESC')
    ->limit(25, 100)
    ->execute();
```

## Config options

 - `formatter`. See [Formats](https://github.com/jamesmoss/flywheel#formats) section of this readme. Defaults to an
   instance of `JamesMoss\Flywheel\Formatter\JSON`.
 - `query_class`. The name of the class that gets returned from `Repository::query()`. By default, Flywheel detects
    if you have APC or APCu installed and uses `CachedQuery` class if applicable, otherwise it just uses `Query`.
 - `document_class`. The name of the class to use when hydrating documenst from the filesystem. Must implement `JamesMoss\Flywheel\DocumentInterface`. Defaults to `JamesMoss\Flywheel\Document`.


## Formats

By default documents are saved and parsed as JSON as it's fast and encoding/decoding is built into PHP.
There are two other serialisation formats you can choose too, YAML and Markdown (with YAML front matter).

You can choose the format by passing it into the `Config` when you initialise it.

```php
$config = new Config('/path/to/writable/directory', array(
    'formatter' => new \JamesMoss\Flywheel\Formatter\YAML,
))
```

The following formatter classes are available.

 - `JamesMoss\Flywheel\Formatter\JSON` - Will attempt to pretty print output if using PHP 5.4+. File extension is `json`.
 - `JamesMoss\Flywheel\Formatter\YAML` - Uses `yaml` file extension, not `yml`.
 - `JamesMoss\Flywheel\Formatter\Markdown` - Takes an optional parameter in the constructor which dictates
    the name of the main field in the resulting `Document` (Defaults to `body`). File extension is `md`. Markdown isn't
    converted into HTML, that's up to you.

**Important** If you use the `YAML` or `Markdown` formatters when using the `--no-dev` flag in Composer you'll need
to manually add `mustangostang\spyc` to your `composer.json`. Flywheel tries to keep it's dependencies to a minimum.

If you write your own formatter it must implement `JamesMoss\Flywheel\Formatter\Format`.

## Todo

- More caching around `Repository::findAll`.
- Indexing.
- HHVM support.
- Abstract the filesystem, something like Gaufrette or Symfony's Filesystem component?
- Events system.
- Option to rehydrate dates as datetime objects?
- More serialisation formats? PHP serialized, PHP raw?
- More mocks in unit tests.
- Simple one-to-one and many-to-one joins.
- Implement proper logical operator precedence in queries
- Add ability to register own comparison operators

## Running tests

There is good test coverage at the moment. If you'd like to run the tests yourself, use the following:

    $ composer install
    $ phpunit

## Contributing

If you spot something I've missed, fork this repo, create a new branch and submit a pull request. Make sure any features you add are covered by unit tests and you don't break any other tests.
