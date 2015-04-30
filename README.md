# Laravel 5.x -> appserver.io integration

## Introduction

This is a first approach, to wrap Laravel 5.x in a native appserver.io Application that takes advantage ouf of the possiblity to pre-load/bootstrap the framework once on appserver.io  startup. All incoming request will only need to be dispatched then, what probably will improve performance.

> As the actual `pthreads` version appserver.io uses is not able to handle closures, this is only a **NOT WORKING** idea, that hopefully will work with appserver.io > 1.1. If you'll try this in the actual version, you'll receive a `PHP Fatal error:  Uncaught exception 'Exception' with message 'Serialization of 'Closure' is not allowed' in /opt/appserver/vendor/appserver-io-labs/laravel-application/src/AppserverIo/Labs/Laravel/Application.php:191` on application server startup.


## Issues
In order to bundle our efforts we would like to collect all issues regarding this package in [the main project repository's issue tracker](https://github.com/appserver-io/appserver/issues).
Please reference the originating repository as the first element of the issue title e.g.:
`[appserver-io/<ORIGINATING_REPO>] A issue I am having`

## Installation

To install the necessary classes, simply do the following, assumed you're on Linux or Mac OS X

```sh
$ cd /opt/appserver
$ bin/php bin/composer.phar require appserver-io-labs/laravel-application dev-master
```

After that install Laravel in the application servers document root and save the following
content

```xml
<?xml version="1.0" encoding="UTF-8"?>
<context 
    name="globalBaseContext" 
    factory="AppserverIo\Labs\Laravel\ApplicationFactory" 
    type="AppserverIo\Labs\Laravel\Application" 
    xmlns="http://www.appserver.io/appserver">
    <classLoaders>
        <classLoader
            name="DgClassLoader"
            interface="ClassLoaderInterface"
            type="AppserverIo\Appserver\Core\ComposerClassLoader"
            factory="AppserverIo\Appserver\Core\ComposerClassLoaderFactory">
            <directories>
                <directory>/vendor</directory>
            </directories>
        </classLoader>
    </classLoaders>
</context>
```

to the `META-INF/context.xml` file of your application, e. g. `/opt/appserver/webapps/laravel/META-INF/context.xml`,
assuming your Laravel application has the name `laravel`.

Restart the application server, and you're all set!

# External Links

* Documentation at [appserver.io](http://appserver.io/get-started/documentation.html)
