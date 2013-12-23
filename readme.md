# Flywheel

[![Build Status](https://travis-ci.org/jamesmoss/flywheel.png?branch=master)](https://travis-ci.org/jamesmoss/flywheel)

A lightweight, flat-file, document database for PHP.

Flywheel is opinionated software. The following is assumed:

- Simple data structures are best.
- You're not going to be storing tens of thousands of documents.

## Requirements

- PHP 5.3+
- Composer

## Installation

Use [Composer](http://getcomposer.org/) to install the flywheel package. Package details [can be found on Packagist.org](https://packagist.org/packages/jamesmoss/flywheel).

Add the following to your `composer.json` and run `composer update`.

    "require": {
        "jamesmoss/flywheel": "dev-master"
    }

You can use this lib without Composer but you'll need to provide your own PSR-0 compatible autoloader. Really, you should just use Composer.

## Use

```
$config = new \Flywheel\Config('path/to/writable/directory');
$repo = new \Flywheel\Repository($config, 'posts');

// Storing a new document
$post = new \Flywheel\Document(array(
    'title'     => 'An introduction to flywheel',
    'dateAdded' => new \DateTime('2013-10-10'),
    'body'      => 'A lightweight, flat-file, document database for PHP...',
    'wordCount' => 7,
));

$id = $repo->store($document);

echo $id; // Czk6SPu4X

$repo->add($document);
$repo->update($document);

// Retrieving documents
$posts = $repo->query()
    ->where('dateAdded', '>', new \DateTime('2013-11-18'))
    ->orderBy('wordCount DESC')
    ->limit(10, 5)
    ->execute();

echo count($posts); // 5 the number of documents returned in this result
echo $posts->total() // 33 the number of documents if no limit was applied. Useful for pagination.

foreach($posts as $post) {
    echo $post->title;
}

// updating documents


```

## Todo

- Indexing
- Caching
- One-to-one and many-to-one joins.
- Events system.
- Atomic updates.
- Result helper methods.
- Option to rehydrate dates as datetime objects?
- Serialisation formats? JSON, PHP serialized, PHP raw?
    
## Running tests

There is 100% test coverage at the moment. If you'd like to run the tests yourself, use the following:

    $ composer update
    $ phpunit

## Contributing

If you spot something I've missed, fork this repo, create a new branch and submit a pull request. Make sure any features you add are covered by unit tests and you don't break any other tests.