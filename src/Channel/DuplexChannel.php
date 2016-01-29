<?php

declare (strict_types = 1);

namespace Recoil\Channel;

/**
 * One end of a duplex (read/write) channel.
 */
interface DuplexChannel extends ReadableChannel, WritableChannel
{
}
