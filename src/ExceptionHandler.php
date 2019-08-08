<?php

declare(strict_types=1);

namespace Quanta\Http;

final class ExceptionHandler
{
    /**
     * The level of the errors to handle in the shutdown function.
     *
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     *
     * @var int
     */
    const FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT;

    /**
     * The min output buffer level
     */

    /**
     * Whether the errors should be handled as exceptions.
     *
     * @var bool
     */
    private $handleErrors;

    /**
     * The last exception handler - should be used to emit a response.
     *
     * @var \Quanta\Http\ExceptionHandlerInterface
     */
    private $renderer;

    /**
     * The exception handlers executed before the renderer - should be used to
     * log exceptions.
     *
     * @var \Quanta\Http\ExceptionHandlerInterface[]
     */
    private $handlers;

    /**
     * Register the handlers with the default renderer.
     *
     * @param bool $debug
     * @param bool $handleErrors
     */
    public static function default(bool $debug = false, bool $handleErrors = false): self
    {
        $renderer = new DefaultExceptionRenderer($debug);

        return new self($handleErrors, $renderer);
    }

    /**
     * Constructor.
     *
     * @param bool                                      $handleErrors
     * @param \Quanta\Http\ExceptionHandlerInterface    $renderer
     * @param \Quanta\Http\ExceptionHandlerInterface    ...$handlers
     */
    public function __construct(
        bool $handleErrors,
        ExceptionHandlerInterface $renderer,
        ExceptionHandlerInterface ...$handlers
    ) {
        $this->handleErrors = $handleErrors;
        $this->renderer = $renderer;
        $this->handlers = $handlers;
    }

    /**
     * Set whether the errors should be handled as exceptions.
     *
     * @param bool $handleErrors
     * @return \Quanta\Http\ExceptionHandler
     */
    public function shouldHandleErrors(bool $handleErrors = true): self
    {
        $this->handleErrors = $handleErrors;

        return $this;
    }

    /**
     * Set the last exception handler.
     *
     * @param \Quanta\Http\ExceptionHandlerInterface $handler
     * @return \Quanta\Http\ExceptionHandler
     */
    public function setRenderer(ExceptionHandlerInterface $handler): self
    {
        $this->renderer = $handler;

        return $this;
    }

    /**
     * Add an exception handler.
     *
     * @param \Quanta\Http\ExceptionHandlerInterface $handler
     * @return \Quanta\Http\ExceptionHandler
     */
    public function addHandler(ExceptionHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Register this exception handler.
     *
     * @return void
     */
    public function register()
    {
        /** @var callable */
        $handleException = [$this, 'handleException'];

        /** @var callable */
        $handleError = [$this, 'handleError'];

        /** @var callable */
        $shutdown = [$this, 'shutdown'];

        set_exception_handler($handleException);
        set_error_handler($handleError);
        register_shutdown_function($shutdown);
    }

    /**
     * Clean the sent headers and unflushed buffers then execute the exception
     * handlers for the given exception.
     *
     * @see https://www.php.net/manual/en/function.set-exception-handler.php
     *
     * @param \Throwable $e
     * @return void
     */
    public function handleException(\Throwable $e)
    {
        header_remove();

        while (ob_get_level() > 0) ob_end_clean();

        foreach ($this->handlers as $handler) {
            $handler($e);
        }

        ($this->renderer)($e);
    }

    /**
     * Throw an error exception from the error when this behavior is enabled and
     * the error level matches the error reporting.
     *
     * Return false otherwise so the error is propagated to the next handler.
     *
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     *
     * @param int       $errno
     * @param string    $errstr
     * @param string    $errfile
     * @param int       $errline
     * @return bool
     * @throws \ErrorException
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if ($this->handleErrors && ($errno & error_reporting()) > 0) {
            throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        }

        return false;
    }

    /**
     * Render fatal errors when this behavior is enabled and the error level
     * matches the error reporting.
     *
     * @see https://www.php.net/manual/en/function.register-shutdown-function.php
     *
     * @return void
     */
    public function shutdown()
    {
        if ($this->handleErrors) {
            $e = error_get_last();

            if (! is_null($e) && ($e['type'] & self::FATAL & error_reporting()) > 0) {
                $this->handleException(
                    new \ErrorException(
                        $e['message'],
                        $e['type'],
                        $e['type'],
                        $e['file'],
                        $e['line']
                    )
                );
            }
        }
    }
}
