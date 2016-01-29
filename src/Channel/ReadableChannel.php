<?php

declare (strict_types = 1);

namespace Recoil\Channel;

use Recoil\Channel\Exception\ChannelClosedException;
use Recoil\Channel\Exception\ChannelLockedException;

/**
 * Interface and specification for coroutine based readable data-channels.
 *
 * A readable data-channel is a stream-like object that produces PHP values
 * rather than binary octets.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
 * "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to
 * be interpreted as described in RFC 2119.
 *
 * @link http://www.ietf.org/rfc/rfc2119.txt
 */
interface ReadableChannel extends Channel
{
    /**
     * Read a value from this channel.
     *
     * The implementation MUST suspend execution of the current strand until a
     * value is available.
     *
     * If the channel is already closed, or is closed while a read operation is
     * pending the implementation MUST throw a ChannelClosedException.
     *
     * The implementation MAY require read operations to be exclusive. If
     * concurrent reads are attempted but not supported the implementation MUST
     * throw a ChannelLockedException.
     *
     * @recoil-coroutine
     *
     * @return mixed                  The value read from the channel.
     * @throws ChannelClosedException if the channel has been closed.
     * @throws ChannelLockedException if concurrent reads are unsupported.
     */
    public function read();
}
