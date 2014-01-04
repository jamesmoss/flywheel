# Flywheel

[![Build Status](https://travis-ci.org/jamesmoss/flywheel.png?branch=master)](https://travis-ci.org/jamesmoss/flywheel)

A lightweight, flat-file, document database for PHP.

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
to a datastore that acts in a similar way to a traditional database but has no
external dependencies. Documents (essentially associative arrays), can be saved and retrieved,
sorted and limited.

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

```php
$config = new \Flywheel\Config('path/to/writable/directory');
$repo = new \Flywheel\Repository('posts', $config);

// Storing a new document
$post = new \Flywheel\Document(array(
    'title'     => 'An introduction to Flywheel',
    'dateAdded' => new \DateTime('2013-10-10'),
    'body'      => 'A lightweight, flat-file, document database for PHP...',
    'wordCount' => 7,
));

echo $post->title; // An introduction to Flywheel
echo $post->wordCount; // 7

$id = $repo->store($post);

echo $id; // Czk6SPu4X
echo $post->id; // Czk6SPu4X

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

// Updating documents
$post->title = 'How to update documents';

// Updates the document (only if it already exists)
$repo->update($post); 

// Updates the document (if it doesnt exist, it gets inserted)
$repo->replace($post); 


// Deleting documents - you can pass a document or it's ID.
$repo->delete($post);
$repo->delete('Czk6SPu4X');

```

## Todo

- Indexing
- Simple one-to-one and many-to-one joins.
- Events system.
- Atomic updates.
- Option to rehydrate dates as datetime objects?
- More serialisation formats? JSON, YAML, PHP serialized, PHP raw?
- More mocks in unit tests.
    
## Running tests

There is good test coverage at the moment. If you'd like to run the tests yourself, use the following:

    $ composer update
    $ phpunit

## Contributing

If you spot something I've missed, fork this repo, create a new branch and submit a pull request. Make sure any features you add are covered by unit tests and you don't break any other tests.