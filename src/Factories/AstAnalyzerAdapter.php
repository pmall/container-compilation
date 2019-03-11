<?php declare(strict_types=1);

namespace Quanta\Container\Factories;

use SuperClosure\Analyzer\AstAnalyzer;

final class AstAnalyzerAdapter implements ClosureCompilerInterface
{
    /**
     * The super closure ast analyzer.
     *
     * @var \SuperClosure\Analyzer\AstAnalyzer
     */
    private $analyzer;

    /**
     * Constructor.
     *
     * @param \SuperClosure\Analyzer\AstAnalyzer $analyzer
     */
    public function __construct(AstAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(\Closure $closure): string
    {
        $analysis = $this->analyzer->analyze($closure);

        if (count($analysis['context']) == 0) {
            return $analysis['code'];
        }

        throw new \LogicException('Closures with context are not compilable');
    }
}
