<?php

declare(strict_types=1);

namespace Quanta\Http;

interface ExceptionHandlerInterface
{
    /**
     * Handle the given exception.
     *
     * @param \Throwable $e
     * @return void
     */
    public function __invoke(\Throwable $e);
}
