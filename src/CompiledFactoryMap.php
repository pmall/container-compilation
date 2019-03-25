<?php declare(strict_types=1);

namespace Quanta\Container;

use SuperClosure\Analyzer\AstAnalyzer;

use Quanta\Container\Factories\Compiler;
use Quanta\Container\Factories\AstAnalyzerAdapter;

final class CompiledFactoryMap implements FactoryMapInterface
{
    /**
     * The factory map to compile.
     *
     * @var \Quanta\Container\FactoryMapInterface
     */
    private $map;

    /**
     * Whether the compiled file must be created every time factories are
     * provided.
     *
     * It allows to test the compilation does not fail while in dev mode.
     *
     * @var bool
     */
    private $cache;

    /**
     * The path of the compiled file.
     *
     * @var string
     */
    private $path;

    /**
     * The factory compiler.
     *
     * It is created when the first factory is compiled.
     *
     * @var null|\Quanta\Container\Factories\Compiler
     */
    private $compiler;

    /**
     * Constructor.
     *
     * @param \Quanta\Container\FactoryMapInterface $map
     * @param bool                                  $cache
     * @param string                                $path
     */
    public function __construct(FactoryMapInterface $map, bool $cache, string $path)
    {
        $this->map = $map;
        $this->cache = $cache;
        $this->path = $path;
        $this->compiler = null;
    }

    /**
     * @inheritdoc
     */
    public function factories(): array
    {
        if (! $this->cache || ! file_exists($this->path)) {
            if (! $this->isPathWritable()) {
                throw new \RuntimeException(
                    sprintf('Container compilation file path is not writable (%s)', $this->path)
                );
            }

            $factories = $this->map->factories();

            foreach ($factories as $id => $factory) {
                try {
                    $compiled[$id] = $this->compiled($factory);
                }

                catch (\Throwable $e) {
                    throw new \LogicException(
                        sprintf('Failed to compile the container factory associated with id \'%s\'', $id), 0, $e
                    );
                }
            }

            $contents = vsprintf('<?php%s%sreturn %s;', [
                PHP_EOL,
                PHP_EOL,
                Utils::ArrayStr($compiled ?? []),
            ]);

            file_put_contents($this->path, $contents);
        }

        return require $this->path;
    }

    /**
     * Return whether the path is writable.
     *
     * @return bool
     */
    private function isPathWritable(): bool
    {
        if (! file_exists($this->path)) {
            return is_writable(dirname($this->path));
        }

        return is_writable($this->path);
    }

    /**
     * Return a string representation of the given callable.
     *
     * @param callable $callable
     * @return string
     */
    public function compiled(callable $callable): string
    {
        if (! $this->compiler) {
            $this->compiler = new Compiler(
                new AstAnalyzerAdapter(
                    new AstAnalyzer
                )
            );
        }

        return ($this->compiler)($callable);
    }
}
