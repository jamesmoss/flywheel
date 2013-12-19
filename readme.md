# Flywheel

[![Build Status](https://travis-ci.org/jamesmoss/flywheel.png?branch=master)](https://travis-ci.org/jamesmoss/flywheel)

A lightweight, flat-file, document database for PHP.

Flywheel is opinionated software

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

// TODO
    
## Running tests

There is 100% test coverage at the moment. If you'd like to run the tests yourself, use the following:

    $ composer update
    $ phpunit

## Contributing

If you spot something I've missed, fork this repo, create a new branch and submit a pull request. Make sure any features you add are covered by unit tests and you don't break any other tests.