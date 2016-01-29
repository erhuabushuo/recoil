<?php

declare (strict_types = 1);

namespace Recoil\Channel;

/**
 * Interface and specification for coroutine based data-channels.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
 * "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to
 * be interpreted as described in RFC 2119.
 *
 * @link http://www.ietf.org/rfc/rfc2119.txt
 */
interface Channel
{
    /**
     * Close this channel.
     *
     * Closing a channel indicates that no more values will be read from the
     * channel. Once a channel is closed, all current and future invocations of
     * read() MUST throw a ChannelClosedException.
     *
     * The implementation SHOULD NOT throw an exception if close() is called on
     * an already-closed channel.
     *
     * @recoil-coroutine
     */
    public function close();

    /**
     * Check if this channel is closed.
     *
     * The implementation MUST return true after close() has been called.
     *
     * @recoil-coroutine
     *
     * @return bool True if the channel has been closed; otherwise, false.
     */
    public function isClosed() : bool;
}
