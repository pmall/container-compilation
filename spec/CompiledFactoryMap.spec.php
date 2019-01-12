<?php

use function Eloquent\Phony\Kahlan\mock;

use org\bovigo\vfs\vfsStream;

use Test\TestFactory;
use Test\TestCompilableFactory;

use Quanta\Container\CompiledFactoryMap;
use Quanta\Container\FactoryMapInterface;

require_once __DIR__ . '/.test/classes.php';

describe('CompiledFactoryMap', function () {

    beforeEach(function () {

        $this->contents = <<<'EOT'
<?php

return [
    'a' => function () { return 'a'; },
    'b' => function () { return 'b'; },
    'c' => function () { return 'c'; },
];

EOT;

    });

    beforeEach(function () {

        $this->delegate = mock(FactoryMapInterface::class);

        $this->root = __DIR__ . '/.test/storage';
        $this->path = $this->root . '/factories.php';

        chmod($this->root, 0755);

        if (file_exists($this->path)) unlink($this->path);

    });

    afterEach(function () {

        if (file_exists($this->path)) unlink($this->path);

    });

    context('when the cache is set to true', function () {

        beforeEach(function () {

            $this->map = new CompiledFactoryMap($this->delegate->get(), true, $this->path);

        });

        it('should implement FactoryMapInterface', function () {

            expect($this->map)->toBeAnInstanceOf(FactoryMapInterface::class);

        });

        describe('->factories()', function () {

            context('when the compiled factories file does not exits', function () {

                context('when the parent directory is not writable', function () {

                    it('should throw a RuntimeException', function () {

                        chmod($this->root, 0444);

                        expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                    });

                });

                context('when the parent directory is writable', function () {

                    context('when the array returned by the delegate ->factories() method is empty', function () {

                        beforeEach(function () {

                            $this->delegate->factories->returns([]);

                        });

                        it('should return an empty array', function () {

                            $test = $this->map->factories();

                            expect($test)->toEqual([]);

                        });

                        it('should write a compiled factory file returning an empty array', function () {

                            $this->map->factories();

                            expect(file_exists($this->path))->toBeTruthy();

                            $test = require $this->path;

                            expect($test)->toEqual([]);

                        });

                    });

                    context('when the array returned by the delegate ->factories() method is not empty', function () {

                        context('when all factories provided by the delegate are compilable', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([
                                    'id1' => $this->factory1 = new TestCompilableFactory('factory'),
                                    'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                    'id3' => $this->factory3 = function () { return 'value'; },
                                ]);

                            });

                            it('should return the array of factories provided by the delegate', function () {

                                $test = $this->map->factories();

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                            it('should write a compiled factory file returning the array of factories provided by the delegate', function () {

                                $this->map->factories();

                                expect(file_exists($this->path))->toBeTruthy();

                                $test = require $this->path;

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                        });

                        context('when one factory provided by the delegate is not compilable', function () {

                            beforeEach(function () {

                                $context = 'context';

                                $this->delegate->factories->returns([
                                    'id1' => new TestCompilableFactory('factory1'),
                                    'id2' => function () use ($context) {},
                                    'id3' => new TestCompilableFactory('factory3'),
                                ]);

                            });

                            it('should throw a LogicException', function () {

                                expect([$this->map, 'factories'])->toThrow(new LogicException);

                            });

                            it('should not create a compiled factory file', function () {

                                try { $this->map->factories(); }

                                catch (Throwable $e) {}

                                expect(file_exists($this->path))->toBeFalsy();

                            });

                        });

                    });

                });

            });

            context('when the compiled factories file exists', function () {

                beforeEach(function () {

                    file_put_contents($this->path, $this->contents);

                });

                it('should not call the delegate ->factories() method', function () {

                    $this->map->factories();

                    $this->delegate->factories->never()->called();

                });

                it('should return the array of factories contained in the compiled factories file', function () {

                    $test = $this->map->factories();

                    expect($test)->toBeAn('array');
                    expect($test)->toHaveLength(3);
                    expect($test['a']())->toEqual('a');
                    expect($test['b']())->toEqual('b');
                    expect($test['c']())->toEqual('c');

                });

                it('should not change the content of the compiled factories file', function () {

                    $this->map->factories();

                    $test = require $this->path;

                    expect($test)->toBeAn('array');
                    expect($test)->toHaveLength(3);
                    expect($test['a']())->toEqual('a');
                    expect($test['b']())->toEqual('b');
                    expect($test['c']())->toEqual('c');

                });

            });

        });

    });

    context('when the cache is set to false', function () {

        beforeEach(function () {

            $this->map = new CompiledFactoryMap($this->delegate->get(), false, $this->path);

        });

        it('should implement FactoryMapInterface', function () {

            expect($this->map)->toBeAnInstanceOf(FactoryMapInterface::class);

        });

        describe('->factories()', function () {

            context('when the compiled factories file does not exist', function () {

                context('when the parent directory is not writable', function () {

                    it('should throw a RuntimeException', function () {

                        chmod($this->root, 0444);

                        expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                    });

                });

                context('when the parent directory is writable', function () {

                    context('when the array returned by the delegate ->factories() method is empty', function () {

                        beforeEach(function () {

                            $this->delegate->factories->returns([]);

                        });

                        it('should return an empty array', function () {

                            $test = $this->map->factories();

                            expect($test)->toEqual([]);

                        });

                        it('should write a compiled factories file returning an empty array', function () {

                            $this->map->factories();

                            expect(file_exists($this->path))->toBeTruthy();

                            $test = require $this->path;

                            expect($test)->toEqual([]);

                        });

                    });

                    context('when the array returned by the delegate ->factories() method is not empty', function () {

                        context('when all factories provided by the delegate are compilable', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([
                                    'id1' => $this->factory1 = new TestCompilableFactory('factory'),
                                    'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                    'id3' => $this->factory3 = function () { return 'value'; },
                                ]);

                            });

                            it('should return the array of factories provided by the delegate', function () {

                                $test = $this->map->factories();

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                            it('should write a compiled factory file returning the array of factories provided by the delegate', function () {

                                $this->map->factories();

                                expect(file_exists($this->path))->toBeTruthy();

                                $test = require $this->path;

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                        });

                        context('when one factory provided by the delegate is not compilable', function () {

                            beforeEach(function () {

                                $context = 'context';

                                $this->delegate->factories->returns([
                                    'id1' => new TestCompilableFactory('factory1'),
                                    'id2' => function () use ($context) {},
                                    'id3' => new TestCompilableFactory('factory3'),
                                ]);

                            });

                            it('should throw a LogicException', function () {

                                expect([$this->map, 'factories'])->toThrow(new LogicException);

                            });

                            it('should not create a compiled factory file', function () {

                                try { $this->map->factories(); }

                                catch (Throwable $e) {}

                                expect(file_exists($this->path))->toBeFalsy();

                            });

                        });

                    });

                });

            });

            context('when the compiled factories file exists', function () {

                beforeEach(function () {

                    file_put_contents($this->path, $this->contents);

                });

                context('when the compiled factory file is not writable', function () {

                    it('should throw a RuntimeException', function () {

                        chmod($this->path, 0444);

                        expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                    });

                });

                context('when the compiled factory file is writable', function () {

                    context('when the array returned by the delegate ->factories() method is empty', function () {

                        beforeEach(function () {

                            $this->delegate->factories->returns([]);

                        });

                        it('should return an empty array', function () {

                            $test = $this->map->factories();

                            expect($test)->toEqual([]);

                        });

                        it('should change the content of the compiled factories file so it returns an empty array', function () {

                            $this->map->factories();

                            $test = require $this->path;

                            expect($test)->toEqual([]);

                        });

                    });

                    context('when the array returned by the delegate ->factories() method is not empty', function () {

                        context('when all factories provided by the delegate are compilable', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([
                                    'id1' => $this->factory1 = new TestCompilableFactory('factory'),
                                    'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                    'id3' => $this->factory3 = function () { return 'value'; },
                                ]);

                            });

                            it('should return the array of factories provided by the delegate', function () {

                                $test = $this->map->factories();

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                            it('should change the content of the compiled factory file returning the array of factories provided by the delegate', function () {

                                $this->map->factories();

                                $test = require $this->path;

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['id1'])->toEqual($this->factory1);
                                expect($test['id2'])->toEqual($this->factory2);
                                expect($test['id3']())->toEqual('value');

                            });

                        });

                        context('when one factory provided by the delegate is not compilable', function () {

                            beforeEach(function () {

                                $context = 'context';

                                $this->delegate->factories->returns([
                                    'id1' => new TestCompilableFactory('factory1'),
                                    'id2' => function () use ($context) {},
                                    'id3' => new TestCompilableFactory('factory3'),
                                ]);

                            });

                            it('should throw a LogicException', function () {

                                expect([$this->map, 'factories'])->toThrow(new LogicException);

                            });

                            it('should not change the content of the compiled factories file', function () {

                                try { $this->map->factories(); }

                                catch (Throwable $e) {}

                                $test = require $this->path;

                                expect($test)->toBeAn('array');
                                expect($test)->toHaveLength(3);
                                expect($test['a']())->toEqual('a');
                                expect($test['b']())->toEqual('b');
                                expect($test['c']())->toEqual('c');

                            });

                        });

                    });

                });

            });

        });

    });

});
