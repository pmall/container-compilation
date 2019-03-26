<?php

use function Eloquent\Phony\Kahlan\mock;

use Psr\Container\ContainerInterface;

use Quanta\Container\CompiledFactoryMap;
use Quanta\Container\FactoryMapInterface;

require_once __DIR__ . '/.test/classes.php';

describe('CompiledFactoryMap', function () {

    beforeEach(function () {

        $this->delegate = mock(FactoryMapInterface::class);

    });

    context('when the path of the compiled file is empty', function () {

        beforeEach(function () {

            $this->delegate->factories->returns([
                'id1' => $this->factory1 = function() {},
                'id2' => $this->factory2 = function() {},
                'id3' => $this->factory3 = function() {},
            ]);

        });

        context('when the cache is set to true', function () {

            beforeEach(function () {

                $this->map = new CompiledFactoryMap($this->delegate->get(), true, '');

            });

            it('should implement FactoryMapInterface', function () {

                expect($this->map)->toBeAnInstanceOf(FactoryMapInterface::class);

            });

            it('should return the array of factories provided by the delegate', function () {

                $test = $this->map->factories();

                expect($test)->toBeAn('array');
                expect($test)->toHaveLength(3);
                expect($test['id1'])->toBe($this->factory1);
                expect($test['id2'])->toBe($this->factory2);
                expect($test['id3'])->toBe($this->factory3);

            });

        });

        context('when the cache is set to false', function () {

            beforeEach(function () {

                $this->map = new CompiledFactoryMap($this->delegate->get(), false, '');

            });

            it('should implement FactoryMapInterface', function () {

                expect($this->map)->toBeAnInstanceOf(FactoryMapInterface::class);

            });

            it('should return the array of factories provided by the delegate', function () {

                $test = $this->map->factories();

                expect($test)->toBeAn('array');
                expect($test)->toHaveLength(3);
                expect($test['id1'])->toBe($this->factory1);
                expect($test['id2'])->toBe($this->factory2);
                expect($test['id3'])->toBe($this->factory3);

            });

        });

    });

    context('when the path of the compiled file is not empty', function () {

        beforeEach(function () {

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

                beforeEach(function () {

                    $this->container = mock(ContainerInterface::class);

                });

                context('when the compiled file does not exits', function () {

                    context('when the parent directory is not writable', function () {

                        it('should throw a RuntimeException', function () {

                            chmod($this->root, 0444);

                            expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                        });

                    });

                    context('when the parent directory is writable', function () {

                        context('when the array of factories provided by the delegate is empty', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([]);

                            });

                            it('should return an empty array', function () {

                                $test = $this->map->factories();

                                expect($test)->toEqual([]);

                            });

                            it('should write a compiled file returning an empty array', function () {

                                $this->map->factories();

                                expect(file_exists($this->path))->toBeTruthy();

                                $test = require $this->path;

                                expect($test)->toEqual([]);

                            });

                        });

                        context('when the array of factories provided by the delegate is not empty', function () {

                            context('when all factories provided by the delegate are compilable', function () {

                                beforeEach(function () {

                                    $this->delegate->factories->returns([
                                        'id1' => $this->factory1 = new Test\TestCompilableFactory('factory'),
                                        'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                        'id3' => $this->factory3 = function () { return 'value'; },
                                    ]);

                                });

                                it('should return the array of factories provided by the delegate', function () {

                                    $test = $this->map->factories();

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                                it('should write a compiled file returning the array of factories provided by the delegate', function () {

                                    $this->map->factories();

                                    expect(file_exists($this->path))->toBeTruthy();

                                    $test = require $this->path;

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                            });

                            context('when one factory provided by the delegate is not compilable', function () {

                                beforeEach(function () {

                                    $context = 'context';

                                    $this->delegate->factories->returns([
                                        'id1' => new Test\TestCompilableFactory('factory1'),
                                        'id2' => function () use ($context) {},
                                        'id3' => new Test\TestCompilableFactory('factory3'),
                                    ]);

                                });

                                it('should throw a LogicException', function () {

                                    expect([$this->map, 'factories'])->toThrow(new LogicException);

                                });

                                it('should not create a compiled file', function () {

                                    try { $this->map->factories(); }

                                    catch (Throwable $e) {}

                                    expect(file_exists($this->path))->toBeFalsy();

                                });

                            });

                        });

                    });

                });

                context('when the compiled file exists', function () {

                    beforeEach(function () {

                        file_put_contents($this->path, <<<'EOT'
<?php

return [
    'a' => function () { return 'a'; },
    'b' => function () { return 'b'; },
    'c' => function () { return 'c'; },
];

EOT
                        );

                    });

                    it('should not call the delegate ->factories() method', function () {

                        $this->map->factories();

                        $this->delegate->factories->never()->called();

                    });

                    it('should return the array of factories contained in the compiled file', function () {

                        $test = $this->map->factories();

                        expect($test)->toBeAn('array');
                        expect($test)->toHaveLength(3);
                        expect($test['a']($this->container->get()))->toEqual('a');
                        expect($test['b']($this->container->get()))->toEqual('b');
                        expect($test['c']($this->container->get()))->toEqual('c');

                    });

                    it('should not update the content of the compiled file', function () {

                        $this->map->factories();

                        $test = require $this->path;

                        expect($test)->toBeAn('array');
                        expect($test)->toHaveLength(3);
                        expect($test['a']($this->container->get()))->toEqual('a');
                        expect($test['b']($this->container->get()))->toEqual('b');
                        expect($test['c']($this->container->get()))->toEqual('c');

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

                beforeEach(function () {

                    $this->container = mock(ContainerInterface::class);

                });

                context('when the compiled file does not exist', function () {

                    context('when the parent directory is not writable', function () {

                        it('should throw a RuntimeException', function () {

                            chmod($this->root, 0444);

                            expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                        });

                    });

                    context('when the parent directory is writable', function () {

                        context('when the array of factories provided by the delegate is empty', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([]);

                            });

                            it('should return an empty array', function () {

                                $test = $this->map->factories();

                                expect($test)->toEqual([]);

                            });

                            it('should write a compiled file returning an empty array', function () {

                                $this->map->factories();

                                expect(file_exists($this->path))->toBeTruthy();

                                $test = require $this->path;

                                expect($test)->toEqual([]);

                            });

                        });

                        context('when the array of factories provided by the delegate is not empty', function () {

                            context('when all factories provided by the delegate are compilable', function () {

                                beforeEach(function () {

                                    $this->delegate->factories->returns([
                                        'id1' => $this->factory1 = new Test\TestCompilableFactory('factory'),
                                        'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                        'id3' => $this->factory3 = function () { return 'value'; },
                                    ]);

                                });

                                it('should return the array of factories provided by the delegate', function () {

                                    $test = $this->map->factories();

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                                it('should write a compiled file returning the array of factories provided by the delegate', function () {

                                    $this->map->factories();

                                    expect(file_exists($this->path))->toBeTruthy();

                                    $test = require $this->path;

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                            });

                            context('when one factory provided by the delegate is not compilable', function () {

                                beforeEach(function () {

                                    $context = 'context';

                                    $this->delegate->factories->returns([
                                        'id1' => new Test\TestCompilableFactory('factory1'),
                                        'id2' => function () use ($context) {},
                                        'id3' => new Test\TestCompilableFactory('factory3'),
                                    ]);

                                });

                                it('should throw a LogicException', function () {

                                    expect([$this->map, 'factories'])->toThrow(new LogicException);

                                });

                                it('should not create a compiled file', function () {

                                    try { $this->map->factories(); }

                                    catch (Throwable $e) {}

                                    expect(file_exists($this->path))->toBeFalsy();

                                });

                            });

                        });

                    });

                });

                context('when the compiled file exists', function () {

                    beforeEach(function () {

                        file_put_contents($this->path, <<<'EOT'
<?php

return [
    'a' => function () { return 'a'; },
    'b' => function () { return 'b'; },
    'c' => function () { return 'c'; },
];

EOT
                        );

                    });

                    context('when the compiled file is not writable', function () {

                        it('should throw a RuntimeException', function () {

                            chmod($this->path, 0444);

                            expect([$this->map, 'factories'])->toThrow(new RuntimeException);

                        });

                    });

                    context('when the compiled file is writable', function () {

                        context('when the array of factories provided by the delegate is empty', function () {

                            beforeEach(function () {

                                $this->delegate->factories->returns([]);

                            });

                            it('should return an empty array', function () {

                                $test = $this->map->factories();

                                expect($test)->toEqual([]);

                            });

                            it('should update the content of the compiled file so it returns an empty array', function () {

                                $this->map->factories();

                                $test = require $this->path;

                                expect($test)->toEqual([]);

                            });

                        });

                        context('when the array of factories provided by the delegate is not empty', function () {

                            context('when all factories provided by the delegate are compilable', function () {

                                beforeEach(function () {

                                    $this->delegate->factories->returns([
                                        'id1' => $this->factory1 = new Test\TestCompilableFactory('factory'),
                                        'id2' => $this->factory2 = ['\\Test\\TestFactory'::class, 'createStatic'],
                                        'id3' => $this->factory3 = function () { return 'value'; },
                                    ]);

                                });

                                it('should return the array of factories provided by the delegate', function () {

                                    $test = $this->map->factories();

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                                it('should update the content of the compiled file returning the array of factories provided by the delegate', function () {

                                    $this->map->factories();

                                    $test = require $this->path;

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['id1']($this->container->get()))->toEqual('factory');
                                    expect($test['id2']($this->container->get()))->toEqual('static');
                                    expect($test['id3']($this->container->get()))->toEqual('value');

                                });

                            });

                            context('when one factory provided by the delegate is not compilable', function () {

                                beforeEach(function () {

                                    $context = 'context';

                                    $this->delegate->factories->returns([
                                        'id1' => new Test\TestCompilableFactory('factory1'),
                                        'id2' => function () use ($context) {},
                                        'id3' => new Test\TestCompilableFactory('factory3'),
                                    ]);

                                });

                                it('should throw a LogicException', function () {

                                    expect([$this->map, 'factories'])->toThrow(new LogicException);

                                });

                                it('should not update the content of the compiled file', function () {

                                    try { $this->map->factories(); }

                                    catch (Throwable $e) {}

                                    $test = require $this->path;

                                    expect($test)->toBeAn('array');
                                    expect($test)->toHaveLength(3);
                                    expect($test['a']($this->container->get()))->toEqual('a');
                                    expect($test['b']($this->container->get()))->toEqual('b');
                                    expect($test['c']($this->container->get()))->toEqual('c');

                                });

                            });

                        });

                    });

                });

            });

        });

    });

});
