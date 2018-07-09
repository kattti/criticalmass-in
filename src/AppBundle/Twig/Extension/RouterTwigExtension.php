<?php declare(strict_types=1);

namespace AppBundle\Twig\Extension;

use AppBundle\Criticalmass\Router\ObjectRouter;
use AppBundle\EntityInterface\RouteableInterface;

class RouterTwigExtension extends \Twig_Extension
{
    /** @var ObjectRouter $router */
    protected $router;

    public function __construct(ObjectRouter $router)
    {
        $this->router = $router;
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('objectPath', [$this, 'objectPath'], [
                'is_safe' => ['raw'],
            ]),
        ];
    }

    public function objectPath(RouteableInterface $object, string $routeName = null): string
    {
        return $this->router->generate($object, $routeName);
    }

    public function getName(): string
    {
        return 'router_extension';
    }
}
