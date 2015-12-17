<?php

declare (strict_types = 1);

namespace Recoil\React;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Recoil\Kernel\Api;
use Recoil\Kernel\DispatchSource;
use Recoil\Kernel\Kernel;
use Recoil\Kernel\Strand;
use RuntimeException;
use Throwable;

/**
 * A Recoil coroutine kernel based on a ReactPHP event loop.
 */
final class ReactKernel implements Kernel
{
    /**
     * Execute a coroutine on a new kernel.
     *
     * This method blocks until the all work on the kernel is complete.
     *
     * The coroutine can be a generator object, or a generator function.
     *
     * @param Generator|callable $coroutine The coroutine to execute.
     * @param LoopInterface|null $eventLoop The event loop to use (null = default).
     *
     * @return mixed The result of the coroutine.
     */
    public static function start($coroutine, LoopInterface $eventLoop = null)
    {
        $kernel = new self($eventLoop);
        $strand = $kernel->execute($coroutine);

        $resolved = false;
        $result = null;

        $strand->promise()->done(
            static function ($value) use (&$resolved, &$result) {
                $resolved = true;
                $result = $value;
            },
            static function (Throwable $exception) {
                throw $exception;
            }
        );

        $kernel->eventLoop->run();

        if (!$resolved) {
            throw new RuntimeException('The coroutine did not complete.');
        }

        return $result;
    }

    /**
     * @param LoopInterface $eventLoop The event loop.
     * @param Api           $api       The kernel API.
     */
    public function __construct(LoopInterface $eventLoop = null, Api $api = null)
    {
        $this->eventLoop = $eventLoop ?: Factory::create();
        $this->api = $api ?: new ReactApi($this->eventLoop);
    }

    /**
     * Start a new strand of execution.
     *
     * The coroutine can be a generator object, or a generator function.
     *
     * The implementation must delay execution of the strand until the next
     * 'tick' of the kernel to allow the user to inspect the strand object
     * before execution begins.
     *
     * @param Generator|callable $coroutine The coroutine to execute.
     *
     * @return Strand
     */
    public function execute($coroutine) : Strand
    {
        $strand = new ReactStrand($this->api);

        $this->eventLoop->futureTick(
            function () use ($strand, $coroutine) {
                $strand->start($coroutine);
            }
        );

        return $strand;
    }

    /**
     * @var LoopInterface The event loop.
     */
    private $eventLoop;

    /**
     * @var Api The kernel API.
     */
    private $api;
}
