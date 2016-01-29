<?php

declare (strict_types = 1);

namespace Recoil\Channel;

use InvalidArgumentException;
use Recoil\Channel\Exception\ChannelClosedException;
use Recoil\Channel\Exception\ChannelLockedException;

/**
 * Interface and specification for coroutine based writable data-channels.
 *
 * A writable data-channel is a stream-like object that consumes PHP values
 * rather than binary octets.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
 * "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to
 * be interpreted as described in RFC 2119.
 *
 * @link http://www.ietf.org/rfc/rfc2119.txt
 */
interface WritableChannel
{
    /**
     * Write a value to this channel.
     *
     * The implementation MUST throw an InvalidArgumentException if the type of
     * the given value is unsupported.
     *
     * The implementation MAY suspend execution of the current strand until the
     * value is delivered.
     *
     * If the channel is already closed, or is closed while a write operation is
     * pending the implementation MUST throw a ChannelClosedException.
     *
     * The implementation MAY require write operations to be exclusive. If
     * concurrent writes are attempted but not supported the implementation MUST
     * throw a ChannelLockedException.
     *
     * @recoil-coroutine
     *
     * @param mixed $value The value to write to the channel.
     *
     * @throws ChannelClosedException   if the channel has been closed.
     * @throws ChannelLockedException   if concurrent writes are unsupported.
     * @throws InvalidArgumentException if the type of $value is unsupported.
     */
    public function write($value);
}
