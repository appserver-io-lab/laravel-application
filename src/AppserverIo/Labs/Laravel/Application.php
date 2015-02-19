<?php

/**
 * AppserverIo\Labs\Laravel\Application
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
*
* PHP version 5
*
* @author    Tim Wagner <tw@appserver.io>
* @copyright 2015 TechDivision GmbH <info@appserver.io>
* @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
* @link      https://github.com/appserver-io-labs/laravel-application
* @link      http://www.appserver.io
*/

namespace AppserverIo\Labs\Laravel;

use AppserverIo\Storage\GenericStackable;
use AppserverIo\Psr\Application\ApplicationInterface;
use AppserverIo\Appserver\Core\Interfaces\ClassLoaderInterface;
use AppserverIo\Appserver\Core\Api\Node\ClassLoaderNodeInterface;

/**
 * Thread-safe Laravel 5.x application implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io-labs/laravel-application
 * @link      http://www.appserver.io
 */
class Application extends \Thread implements ApplicationInterface
{

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @return \AppserverIo\Psr\Application\ApplicationInterface The connected application
     */
    public function connect()
    {
        $this->run();
    }

    /**
     * Injects the storage for the class loaders.
     *
     * @param \AppserverIo\Storage\GenericStackable $classLoaders The storage for the class loaders
     *
     * @return void
     */
    public function injectClassLoaders(GenericStackable $classLoaders)
    {
        $this->classLoaders = $classLoaders;
    }

    /**
     * Return the requested class loader instance
     *
     * @param string $identifier The unique identifier of the requested class loader
     *
     * @return \AppserverIo\Appserver\Core\Interfaces\ClassLoaderInterface The class loader instance
     */
    public function getClassLoader($identifier)
    {
        if (isset($this->classLoaders[$identifier])) {
            return $this->classLoaders[$identifier];
        }
    }

    /**
     * Return the class loaders.
     *
     * @return \AppserverIo\Storage\GenericStackable The class loader instances
     */
    public function getClassLoaders()
    {
        return $this->classLoaders;
    }

    /**
     * Injects an additional class loader.
     *
     * @param \AppserverIo\Appserver\Core\Interfaces\ClassLoaderInterface   $classLoader   A class loader to put on the class loader stack
     * @param \AppserverIo\Appserver\Core\Api\Node\ClassLoaderNodeInterface $configuration The class loader's configuration
     *
     * @return void
     */
    public function addClassLoader(ClassLoaderInterface $classLoader, ClassLoaderNodeInterface $configuration)
    {
        $this->classLoaders[$configuration->getName()] = $classLoader;
    }

    /**
     * Registers all class loaders injected to the applications in the opposite
     * order as they have been injected.
     *
     * @return void
     */
    public function registerClassLoaders()
    {
        foreach ($this->getClassLoaders() as $classLoader) {
            $classLoader->register(true, true);
        }
    }

    /**
     * The application threads main method that allows us to bootstrap
     * Laravel in a separate context/environment.
     *
     * @return void
     */
    public function run()
    {

        /*
         |--------------------------------------------------------------------------
         | Register The ClassLoader(s)
         |--------------------------------------------------------------------------
         |
         | The first thing we will do is to register the class loaders, because
         | as we're inside a thread, Laravel needs the Composer class loader to
         | find the class definition.
         |
         */

        $this->registerClassLoaders();

        /*
         |--------------------------------------------------------------------------
         | Create The Application
         |--------------------------------------------------------------------------
         |
         | After register the class loaders we will do is create a new Laravel
         | application instance which serves as the "glue" for all the components
         | of Laravel, and is the IoC container for the system binding all of the
         | various parts.
         |
         */

        $app = new \Illuminate\Foundation\Application(
            $this->getWebappPath()
        );

        /*
         |--------------------------------------------------------------------------
         | Bind Important Interfaces
         |--------------------------------------------------------------------------
         |
         | Next, we need to bind some important interfaces into the container so
         | we will be able to resolve them when needed. The kernels serve the
         | incoming requests to this application from both the web and CLI.
         |
        */

        $app->singleton(
            'Illuminate\Contracts\Http\Kernel',
            'App\Http\Kernel'
        );

        $app->singleton(
            'Illuminate\Contracts\Console\Kernel',
            'App\Console\Kernel'
        );

        $app->singleton(
            'Illuminate\Contracts\Debug\ExceptionHandler',
            'App\Exceptions\Handler'
        );

        /*
         |--------------------------------------------------------------------------
         | Bootstrap The Application
         |--------------------------------------------------------------------------
         |
         | Once we have the application, we can simply call the run method,
         | which will execute the request and send the response back to
         | the client's browser allowing them to enjoy the creative
         | and wonderful application we have prepared for them.
         |
         */
        $this->kernel = $app->make('Illuminate\Contracts\Http\Kernel');

       /*
        |--------------------------------------------------------------------------
        | Wait For Requests
        |--------------------------------------------------------------------------
        |
        | As in an application server each application needs a seperate context
        | to avoid the "Can not redeclare ..." fatal error, this thread will run
        | forever an gives the application that context.
        */
        while (true) {
            $this->wait(1000000);
        }
    }

    /**
     * Injects the application name.
     *
     * @param string $name The application name
     *
     * @return void
     */
    public function injectName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the application name (that has to be the class namespace,
     * e. g. TechDivision\Example).
     *
     * @return string The application name
    */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Injects the absolute path to the application servers base directory.
     *
     * @param string $baseDirectory The base directory
     */
    public function injectBaseDirectory($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * Returns the absolute path to the applications servers base directory,
     * which is /opt/appserver by default.
     *
     * @param string $directoryToAppend Directory to append before returning the base directory
     *
     * @return string The application server's base directory
    */
    public function getBaseDirectory($directoryToAppend = null)
    {
        $baseDirectory = $this->baseDirectory;
        if ($directoryToAppend != null) {
            $baseDirectory .= $directoryToAppend;
        }
        return $baseDirectory;
    }

    /**
     * Injects the absolute path to the applications base directory.
     *
     * @param string $appBase The app base directory
     *
     * @return void
     */
    public function injectAppBase($appBase)
    {
        $this->appBase = $appBase;
    }

    /**
     * Returns the absolute path to the applications base directory.
     *
     * @return string The app base directory
     */
    public function getAppBase()
    {
        return $this->appBase;
    }

    /**
     * Injects the absolute path to the web application.
     *
     * @param string $webappPath The path to the web application
     *
     * @return void
     */
    public function injectWebappPath($webappPath)
    {
        $this->webappPath = $webappPath;
    }

    /**
     * Returns the path to the web application.
     *
     * @return string The path to the web application
     */
    public function getWebappPath()
    {
        return $this->webappPath;
    }
}
