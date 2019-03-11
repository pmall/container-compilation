<?php

namespace Test;

use Psr\Container\ContainerInterface;

use Quanta\Container\Factories\Compiler;
use Quanta\Container\Factories\CompiledFactory;
use Quanta\Container\Factories\CompilableFactoryInterface;

final class TestFactory
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function createStatic()
    {
        return 'static';
    }

    public function create()
    {
        return 'instance';
    }

    public function __invoke(ContainerInterface $container)
    {
        //
    }
}

final class TestCompilableFactory implements CompilableFactoryInterface
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __invoke(ContainerInterface $container)
    {

    }

    public function compiled(Compiler $compiler): CompiledFactory
    {
        return new CompiledFactory('container', vsprintf('return \'%s\';', [
            $this->name,
        ]));
    }
}
