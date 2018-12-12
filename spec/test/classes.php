<?php

namespace Test;

use Psr\Container\ContainerInterface;

use Quanta\Container\Compilation\Template;
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

    }

    public function create()
    {

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

    public function compiled(Template $template): string
    {
        return sprintf('new %s(\'%s\')', self::class, $this->name);
    }
}
