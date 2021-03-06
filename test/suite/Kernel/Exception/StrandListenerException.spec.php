<?php

declare (strict_types = 1); // @codeCoverageIgnore

namespace Recoil\Kernel\Exception;

use Eloquent\Phony\Phony;
use Error;
use Recoil\Kernel\Listener;
use Recoil\Kernel\Strand;

describe(StrandListenerException::class, function () {

    beforeEach(function () {
        $this->strand = Phony::mock(Strand::class);
        $this->strand->id->returns(123);
        $this->previous = new Error('<message>');

        $this->subject = new StrandListenerException(
            $this->strand->mock(),
            $this->previous
        );
    });

    it('produces a useful message', function () {
        expect($this->subject->getMessage())->to->equal(
            'Unhandled exception in listener for strand #123: Error (<message>).'
        );
    });

    it('exposes the exited strand', function () {
        expect($this->subject->strand())->to->equal($this->strand->mock());
    });

    it('exposes the previous exception', function () {
        expect($this->subject->getPrevious())->to->equal($this->previous);
    });

});
