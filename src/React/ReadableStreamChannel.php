<?php

declare (strict_types = 1);

namespace Recoil\React;

use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromisorInterface;
use React\Stream\ReadableStreamInterface;
use Recoil\Channel\Exception\ChannelLockedException;
use Recoil\Kernel\Strand;
use Recoil\Kernel\StrandTrait;

final class ReadableStreamChannel implements ReadableChannel
{
    public function __construct(ReadableStreamInterface $stream, int $bufferSize = 0)
    {
        $this->stream = $stream;
        $this->bufferSize = $bufferSize;

        $this->stream->pause();

        $this->stream->on(
            'data',
            function ($buffer) {
                $this->buffer .= $buffer;

                if ($this->strand) {
                    $this->strand->resume();
                } elseif (\strlen($this->buffer) >= $this->bufferSize) {
                    $this->stream->pause();
                    $this->isPaused = true;
                }
            }
        );

        $this->stream->on(
            'end',
            function () {
                $this->isEnd = true;

                if ($this->strand) {
                    $this->strand->resume();
                }
            }
        );

        $this->stream->on(
            'close',
            function () {
            }
        );

        $this->stream->on(
            'error',
            function ($error) {
            }
        );
    }

    public function read()
    {
        if ($this->strand) {
            throw new ChannelLockedException();
        } elseif ($this->hasData) {
            $this->hasData = false;
            $buffer = $this->buffer;
            $this->buffer = '';

            return $buffer;
        } elseif ($this->stream === null) {
            throw new ChannelClosedException();
        }

        yield Recoil::suspend(
            function ($strand) {
                $this->strand = $strand;

                if ($this->isPaused) {
                    $this->stream->resume();
                    $this->isPaused = false;
                }
            }
        );

        return $buffer;
    }

    public function close()
    {
        if ($this->stream === null) {
            return;
            yield;
        }

        $this->stream->close();
        $this->stream = null;
    }

    public function isClosed() : bool
    {
        return $this->stream !== null;
        yield;
    }

    private $stream;
    private $buffer;
    private $bufferSize;
    private $isPaused = true;
    private $isEnd = false;
}
