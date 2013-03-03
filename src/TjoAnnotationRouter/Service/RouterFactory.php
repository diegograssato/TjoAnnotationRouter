<?php
namespace TjoAnnotationRouter\Service;

use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Mvc\Router\Console\SimpleRouteStack as ConsoleRouter;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Console\Console;

/**
 * This is a modified version {@see Zend\Mvc\Service\RouterFactory} which
 * adds the extra annotated config to the router.
 *
 * @author Tom Oram <tom@x2k.co.uk>
 */
class RouterFactory implements FactoryInterface
{
    /**
     * Create and return the router
     *
     * Retrieves the "router" key of the Config service, and uses it
     * to instantiate the router. Uses the TreeRouteStack implementation by
     * default.
     *
     * @param  ServiceLocatorInterface        $serviceLocator
     * @param  string|null                     $cName
     * @param  string|null                     $rName
     * @return \Zend\Mvc\Router\RouteStackInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $cName = null, $rName = null)
    {
        $config             = $serviceLocator->get('Config');
        $routePluginManager = $serviceLocator->get('RoutePluginManager');

        if (
            $rName === 'ConsoleRouter' ||                   // force console router
            ($cName === 'router' && Console::isConsole())       // auto detect console
        ) {
            // We are in a console, use console router.
            if (isset($config['console']) && isset($config['console']['router'])) {
                $routerConfig = $config['console']['router'];
            } else {
                $routerConfig = array();
            }

            $router = new ConsoleRouter($routePluginManager);
        } else {
            // This is an HTTP request, so use HTTP router
            $router       = new HttpRouter($routePluginManager);
            $routerConfig = isset($config['router']) ? $config['router'] : array();

            // Add the extra annotation router config
            $annotationRouter = $serviceLocator->get('TjoAnnotationRouter\AnnotationRouter');

            $annotationRouter->updateRouteConfig(
                $config['tjo_annotation_router']['controllers'],
                $routerConfig
            );
        }

        if (isset($routerConfig['route_plugins'])) {
            $router->setRoutePluginManager($routerConfig['route_plugins']);
        }

        if (isset($routerConfig['routes'])) {
            $router->addRoutes($routerConfig['routes']);
        }

        if (isset($routerConfig['default_params'])) {
            $router->setDefaultParams($routerConfig['default_params']);
        }

        return $router;
    }
}
