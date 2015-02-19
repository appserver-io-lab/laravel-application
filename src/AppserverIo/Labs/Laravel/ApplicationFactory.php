<?php

/**
 * AppserverIo\Labs\Laravel\ApplicationFactory
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
use AppserverIo\Appserver\Core\Api\Node\ContextNode;
use AppserverIo\Appserver\Core\Interfaces\ContainerInterface;

/**
 * Factory for the Laravel 5.x application implementation.
*
* @author    Tim Wagner <tw@appserver.io>
* @copyright 2015 TechDivision GmbH <info@appserver.io>
* @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
* @link      https://github.com/appserver-io-labs/laravel-application
* @link      http://www.appserver.io
 */
class ApplicationFactory
{

    /**
     * Visitor method that registers the application in the container.
     *
     * @param \AppserverIo\Appserver\Core\Interfaces\ContainerInterface $container The container instance bind the application to
     * @param \AppserverIo\Appserver\Core\Api\Node\ContextNode          $context   The application configuration
     *
     * @return void
     */
    public static function visit(ContainerInterface $container, ContextNode $context)
    {

        // load the application type
        $contextType = $context->getType();
        $applicationName = $context->getName();
        $baseDirectory = $container->getBaseDirectory();

        // prepare the path to the applications base directory
        $appBase = $container->getAppBase();
        $webappPath =  $appBase . DIRECTORY_SEPARATOR . $applicationName;

        // create a new application instance
        $application = new $contextType();

        // initialize the storage for the class loaders
        $classLoaders = new GenericStackable();

        // initialize the generic instances and information
        $application->injectAppBase($appBase);
        $application->injectName($applicationName);
        $application->injectWebappPath($webappPath);
        $application->injectClassLoaders($classLoaders);
        $application->injectBaseDirectory($baseDirectory);

        // add the configured class loaders
        foreach ($context->getClassLoaders() as $classLoader) {
            if ($classLoaderFactory = $classLoader->getFactory()) {
                // use the factory if available
                $classLoaderFactory::visit($application, $classLoader);
            } else {
                // if not, try to instanciate the class loader directly
                $classLoaderType = $classLoader->getType();
                $application->addClassLoader(new $classLoaderType($classLoader), $classLoader);
            }
        }

        // add the application to the container
        $container->addApplication($application);
    }
}
