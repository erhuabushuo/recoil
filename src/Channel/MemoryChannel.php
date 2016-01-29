<?php

declare (strict_types = 1);

namespace Recoil\Channel;

/**
 * An in-memory simplex channel.
 */
final class MemoryChannel implements ReadableChannel, WritableChannel
{
    /**
     * @param int $bufferSize The number of elements to buffer before calls to write() will block.
     *
     * @return self
     */
    public static function create(int $bufferSize = 0)
    {
        return new self($bufferSize);
    }

    /**
     * Read a value from this channel.
     *
     * Blocks until a value is available.
     *
     * @recoil-coroutine
     *
     * @return mixed                  The value read from the channel.
     * @throws ChannelClosedException if the channel has been closed.
     */
    public function read()
    {
        if ($this->queue === null) {
            throw new ChannelClosedException();
        } elseif ($this->queueSize > 0) {
            $this->queueSize--;
            $fn = $this->queue->dequeue();
            $value = $fn();
        } else {
            $value = yield Recoil::suspend(
                function ($strand) {
                    $this->queueSize--;
                    $this->queue->enqueue($strand);
                }
            );
        }

        return $value;
    }

    /**
     * Write a value to this channel.
     *
     * Execution of the current strand is suspended until the value delivered to
     * a reader.
     *
     * @recoil-coroutine
     *
     * @param mixed $value The value to write to the channel.
     *
     * @throws ChannelClosedException   if the channel has been closed.
     * @throws InvalidArgumentException if the type of $value is unsupported.
     */
    public function write($value)
    {
        if ($this->queue === null) {
            throw new ChannelClosedException();
        } elseif ($this->queueSize < 0) {
            $this->queueSize++;
            $this->queue->dequeue()->resume($value);
        } elseif ($this->bufferSize) {
            $this->bufferSize--;
            $this->queueSize++;
            $this->queue->enqueue(
                function () use ($value) {
                    $this->bufferSize++;

                    return $value;
                }
            );
        } else {
            yield Recoil::suspend(
                function ($strand) use ($value) {
                    $this->queueSize++;
                    $this->queue->enqueue(
                        function () use ($strand, $value) {
                            $strand->resume();

                            return $value;
                        }
                    );
                }
            );
        }
    }

    /**
     * Close this channel.
     *
     * @recoil-coroutine
     */
    public function close()
    {
        if ($this->queue === null) {
            return;
            yield;
        }

        $queue = $this->queue;
        $this->queue = null;
        $exception = new ChannelClosedException();

        foreach ($queue as $strand) {
            $strand->throw($exception);
        }
    }

    /**
     * Check if this channel is closed.
     *
     * The implementation MUST return true after close() has been called.
     *
     * @recoil-coroutine
     *
     * @return bool True if the channel has been closed; otherwise, false.
     */
    public function isClosed() : bool
    {
        return $this->queue === null;
        yield;
    }

    /**
     * @param int $bufferSize The number of elements to buffer before calls to write() will block.
     */
    private function __construct(int $bufferSize = 0)
    {
        $this->bufferSize = $bufferSize;
        $this->queue = new SplQueue();
    }

    /**
     * @var int The number of unused buffer "slots".
     */
    private $bufferSize;

    /**
     * @var int The size of the queue, if size < 0 the queue contains abs(size)
     *          read strands. If size > 0 the queue contains (size) callbacks
     *          that resume the write strand and return the value.
     */
    private $queueSize = 0;

    /**
     * @var SplQueue<callable|Strand>|null
     */
    private $queue;
}
