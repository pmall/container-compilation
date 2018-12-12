<?php

use SuperClosure\Analyzer\AstAnalyzer;

use Quanta\Container\Compilation\AstAnalyzerAdapter;
use Quanta\Container\Compilation\ClosureCompilerInterface;

describe('AstAnalyzerAdapter', function () {

    beforeEach(function () {

        $this->compiler = new AstAnalyzerAdapter(new AstAnalyzer);

    });

    it('should implement ClosureCompilerInterface', function () {

        expect($this->compiler)->toBeAnInstanceOf(ClosureCompilerInterface::class);

    });

    describe('->compiled()', function () {

        context('when the given closure has no context', function () {

            it('should return a string representation of the given closure', function () {

                $test = $this->compiler->compiled(function (string $x) {
                    $value = $x . ':value';

                    return $value;
                });

                expect($test)->toEqual(<<<'EOT'
function (string $x) {
    $value = $x . ':value';
    return $value;
}
EOT
                );

            });

        });

        context('when the given closure has context', function () {

            it('should throw a LogicException', function () {

                $test = function () {
                    $context = 'context';

                    $this->compiler->compiled(function () use ($context) {});
                };

                expect($test)->toThrow(new LogicException);

            });

        });

    });

});
