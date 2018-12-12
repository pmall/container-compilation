<?php

use function Eloquent\Phony\Kahlan\mock;

use Test\TestFactory;
use Test\TestCompilableFactory;

use Quanta\Container\CompiledFactoryMap;
use Quanta\Container\FactoryMapInterface;

require_once __DIR__ . '/test/classes.php';

describe('CompiledFactoryMap', function () {

    beforeEach(function () {

        $this->path = sys_get_temp_dir() . '/quanta-test';

        if (file_exists($this->path)) unlink($this->path);

        $this->delegate = mock(FactoryMapInterface::class);

    });

    describe('->factories()', function () {

        context('when compilation file is writable', function () {

            context('when the delegate does not provide any factory', function () {

                beforeEach(function () {

                    $this->delegate->factories->returns([]);

                });

                it('should return an empty array', function () {

                    foreach ([true, false] as $cache) {
                        $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                        $test = $map->factories();

                        expect($test)->toEqual([]);
                    }

                });

                it('should create a compiled file returning an empty array', function () {

                    foreach ([true, false] as $cache) {
                        $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                        $map->factories();

                        $test = require $this->path;

                        expect($test)->toEqual([]);
                    }

                });

            });

            context('when the delegate provides at least one factory', function () {

                beforeEach(function () {

                    $this->factories = [
                        'factory1' => new TestCompilableFactory('factory1'),
                        'factory2' => new TestCompilableFactory('factory2'),
                        'factory3' => new TestCompilableFactory('factory3'),
                        'factory4' => ['\\Test\\TestFactory', 'createStatic'],
                        'factory5' => function () { return 'value'; },
                    ];

                    $this->delegate->factories->returns($this->factories);

                });

                context('when all factories provided by the delegate are compilable', function () {

                    it('should return the delegate factories', function () {

                        foreach ([true, false] as $cache) {
                            $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                            $test = $map->factories();

                            expect($test)->toBeAn('array');
                            expect($test)->toHaveLength(5);
                            expect($test['factory1'])->toEqual($this->factories['factory1']);
                            expect($test['factory2'])->toEqual($this->factories['factory2']);
                            expect($test['factory3'])->toEqual($this->factories['factory3']);
                            expect($test['factory4'])->toEqual($this->factories['factory4']);
                            expect($test['factory5']())->toEqual($this->factories['factory5']());
                        }

                    });

                    it('should return the same array of factories on every call', function () {

                        foreach ([true, false] as $cache) {
                            $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                            $test1 = $map->factories();
                            $test2 = $map->factories();

                            expect($test1)->toBeAn('array');
                            expect($test2)->toBeAn('array');
                            expect($test1)->toHaveLength(5);
                            expect($test2)->toHaveLength(5);
                            expect($test1['factory1'])->toEqual($test1['factory1']);
                            expect($test1['factory2'])->toEqual($test1['factory2']);
                            expect($test1['factory3'])->toEqual($test1['factory3']);
                            expect($test1['factory4'])->toEqual($test1['factory4']);
                            expect($test1['factory5']())->toEqual($test2['factory5']());
                        }

                    });

                    context('when the cache option is set to false', function () {

                        it('should write to the file only on every call', function () {

                            $map = new CompiledFactoryMap($this->delegate->get(), false, $this->path);

                            $map->factories();

                            $test1 = file_get_contents($this->path);

                            $this->delegate->factories->returns([]);

                            $map->factories();

                            $test2 = file_get_contents($this->path);

                            expect($test1)->not->toEqual($test2);

                        });

                    });

                    context('when the cache option is set to true', function () {

                        it('should write to the file only on the first call', function () {

                            $map = new CompiledFactoryMap($this->delegate->get(), true, $this->path);

                            $map->factories();

                            $test1 = file_get_contents($this->path);

                            $this->delegate->factories->returns([]);

                            $map->factories();

                            $test2 = file_get_contents($this->path);

                            expect($test1)->toEqual($test2);

                        });

                    });

                });

                context('when one factory is an array representing a method call', function () {

                    it('should throw a LogicException', function () {

                        foreach ([true, false] as $cache) {
                            $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                            $this->delegate->factories->returns([
                                'factory1' => new TestCompilableFactory('factory1'),
                                'factory2' => [new TestFactory('factory2'), 'create'],
                                'factory3' => new TestCompilableFactory('factory3'),
                            ]);

                            expect([$map, 'factories'])->toThrow(new LogicException);
                        }

                    });

                });

                context('when one factory is a closure with context', function () {

                    it('should throw a LogicException', function () {

                        foreach ([true, false] as $cache) {
                            $map = new CompiledFactoryMap($this->delegate->get(), $cache, $this->path);

                            $context = 'context';

                            $this->delegate->factories->returns([
                                'factory1' => new TestCompilableFactory('factory1'),
                                'factory2' => function () use ($context) {},
                                'factory3' => new TestCompilableFactory('factory3'),
                            ]);

                            expect([$map, 'factories'])->toThrow(new LogicException);
                        }

                    });

                });

            });

        });

        context('when compilation file is not writable', function () {

            it('should throw a RuntimeException', function () {

                foreach ([true, false] as $cache) {
                    $map = new CompiledFactoryMap($this->delegate->get(), $cache, __DIR__ . '/non/exising/path/compiled.php');

                    expect([$map, 'factories'])->toThrow(new RuntimeException);
                }

            });

        });

    });

});
