<?php

namespace Pyz\Zed\Sales;

class SalesDependencyProvider
{
    protected const FACADE_EXAMPLE = 'FACADE_EXAMPLE';

    public function provideBusinessLayerDependencies($container)
    {
        $container->set(static::FACADE_EXAMPLE, function ($container) {
            return new \stdClass();
        });

        $container->set(static::FACADE_EXAMPLE, static function ($container) {
            return new \stdClass();
        });

        $container->set(static::FACADE_EXAMPLE, fn ($container) => new \stdClass());

        $container->set(static::FACADE_EXAMPLE, new \stdClass());
        $container->set(static::FACADE_EXAMPLE, $this->createExampleFacade($container));

        return $container;
    }

    protected function createExampleFacade($container): object
    {
        return new \stdClass();
    }
}
