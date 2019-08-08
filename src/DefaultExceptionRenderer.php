<?php

declare(strict_types=1);

namespace Quanta\Http;

final class DefaultExceptionRenderer
{
    /**
     * @inheritdoc
     */
    public function __invoke(\Throwable $e, \Throwable ...$exs)
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (ExceptionHandler\Utils::shouldEmitJson($accept)) {
            header('application/json');
            echo ExceptionHandler\Utils::json($e, ...$exs);
        } else {
            header('text/html');
            echo ExceptionHandler\Utils::html('exception.php', $e, ...$exs);
        }
    }
}
