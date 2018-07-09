<?php declare(strict_types=1);

namespace AppBundle\Criticalmass\Router;

use AppBundle\Criticalmass\Router\Annotation\DefaultRoute;
use AppBundle\Criticalmass\Router\Annotation\RouteParameter;
use AppBundle\Entity\Region;
use AppBundle\Entity\Thread;
use AppBundle\EntityInterface\RouteableInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ObjectRouter
{
    /** @var RouterInterface $router */
    protected $router;

    /** @var AnnotationReader $annotationReader */
    protected $annotationReader;

    public function __construct(RouterInterface $router, AnnotationReader $annotationReader)
    {
        $this->router = $router;
        $this->annotationReader = $annotationReader;
    }

    public function generate(RouteableInterface $routeable, string $routeName = null): string
    {
        if (!$routeName) {
            $routeName = $this->getDefaultRouteName($routeable);
        }

        if ($routeName) {
            $parameterList = $this->generateParameterList($routeable, $routeName);

            return $this->router->generate($routeName, $parameterList, UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            $methodName = sprintf('generate%sUrl', $this->getClassname($routeable));

            return $this->$methodName($routeable, $routeName);
        }
    }

    protected function generateThreadUrl(Thread $thread, string $routeName = null): string
    {
        /* Let’s see if this is a city thread */
        if ($thread->getCity()) {
            $route = 'caldera_criticalmass_board_viewcitythread';
        } else {
            $route = 'caldera_criticalmass_board_viewthread';
        }

        $parameterList = $this->generateParameterList($thread, $routeName);

        return $this->router->generate($route, $parameterList, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    protected function generateRegionUrl(Region $region, string $routeName = null): string
    {
        if ($region->getParent() == null) {
            return $this->router->generate(
                'caldera_criticalmass_region_world', [], UrlGeneratorInterface::ABSOLUTE_URL);
        } elseif ($region->getParent()->getParent() == null) {
            return $this->router->generate(
                'caldera_criticalmass_region_world_region_1',
                [
                    'slug1' => $region->getSlug()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL);
        } elseif ($region->getParent()->getParent()->getParent() == null) {
            return $this->router->generate(
                'caldera_criticalmass_region_world_region_2',
                [
                    'slug1' => $region->getParent()->getSlug(),
                    'slug2' => $region->getSlug()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL);
        } elseif ($region->getParent()->getParent()->getParent()->getParent() == null) {
            return $this->router->generate(
                'caldera_criticalmass_region_world_region_3',
                [
                    'slug1' => $region->getParent()->getParent()->getSlug(),
                    'slug2' => $region->getParent()->getSlug(),
                    'slug3' => $region->getSlug()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }

    protected function getDefaultRouteName(RouteableInterface $routeable): ?string
    {
        /* It looks like Doctrine Annotation Reader cannot handle class annotations of Doctrine proxy objects so we do
         * not inject the $routeable itself but its classname */
        $classname = $this->getClassname($routeable);
        $fqcn = sprintf('AppBundle\\Entity\\%s', $classname);

        $reflectionClass = new \ReflectionClass($fqcn);

        $defaultRouteAnnotation = $this->annotationReader->getClassAnnotation($reflectionClass, DefaultRoute::class);

        if ($defaultRouteAnnotation) {
            return $defaultRouteAnnotation->getName();
        }

        return null;
    }

    protected function getRouteParameter(RouteableInterface $routeable, string $variableName): ?string
    {
        $reflectionClass = new \ReflectionClass($routeable);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $key => $property) {
            $parameterAnnotation = $this->annotationReader->getPropertyAnnotation($property, RouteParameter::class);

            if ($parameterAnnotation) {
                if ($parameterAnnotation->getName() !== $variableName) {
                    continue;
                }

                $getMethodName = sprintf('get%s', ucfirst($property->getName()));

                if (!$reflectionClass->hasMethod($getMethodName)) {
                    continue;
                }

                $value = $routeable->$getMethodName();

                if (is_object($value) && $value instanceof RouteableInterface) {
                    $value = $this->getRouteParameter($value, $variableName);
                }

                if (is_object($value) && $value instanceof \DateTime) {
                    $value = $value->format($parameterAnnotation->getDateFormat());
                }

                return (string) $value;
            }
        }

        return null;
    }

    protected function generateParameterList(RouteableInterface $routeable, string $routeName): array
    {
        $route = $this->router->getRouteCollection()->get($routeName);

        $compiledRoute = $route->compile();

        $variableList = $compiledRoute->getVariables();
        $parameterList = [];

        foreach ($variableList as $variableName) {
            $parameterList[$variableName] = $this->getRouteParameter($routeable, $variableName);
        }

        return $parameterList;
    }

    protected function getClassname(RouteableInterface $routeable): string
    {
        $classNameParts = explode('\\', get_class($routeable));
        $className = array_pop($classNameParts);

        return $className;
    }
}
