<?php

declare(strict_types=1);

namespace Quanta\Http;

final class HtmlExceptionRenderer implements ExceptionHandlerInterface
{
    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * Constructor.
     *
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(\Throwable $e)
    {
        $body = $this->body($e);

        http_response_code(500);

        header('content-type: text/html');

        echo $body;
    }

    /**
     * Return the html body for the given exception.
     *
     * @param \Throwable $e
     * @return string
     */
    private function body(\Throwable $e): string
    {
        if ($this->debug) {
            ob_start();
            require __DIR__ . '/../templates/exception.debug.php';
            return ($body = ob_get_clean()) ? $body : '';
        }

        $path = __DIR__ . '/../templates/exception.php';

        return ($body = file_get_contents($path)) ? $body : '';
    }
}
