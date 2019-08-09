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
     * Whether the exceptions should be rendered.
     *
     * @var bool
     */
    private $render;

    /**
     * Whether the errors should be handled as exceptions.
     *
     * @var bool
     */
    private $errors;

    /**
     * The last exception handler.
     *
     * Should be used to emit a response.
     *
     * @var callable
     */
    private $renderer;

    /**
     * The exception handlers executed before the renderer.
     *
     * Should be used to log exceptions.
     *
     * @var callable[]
     */
    private $handlers;

    /**
     * Return an exception handler with the default renderer.
     *
     * @param bool $render
     * @param bool $errors
     * @return \Quanta\Http\ExceptionHandler
     */
    public static function default(bool $render = false, bool $errors = false): self
    {
        return new self($render, $errors, new DefaultExceptionRenderer);
    }

    /**
     * Constructor.
     *
     * @param bool      $render
     * @param bool      $errors
     * @param callable  $renderer
     * @param callable  ...$handlers
     */
    public function __construct(bool $render, bool $errors, callable $renderer, callable ...$handlers)
    {
        $this->render = $render;
        $this->errors = $errors;
        $this->renderer = $renderer;
        $this->handlers = $handlers;
    }

    /**
     * Set whether the exceptions should be rendered.
     *
     * @param bool $render
     * @return \Quanta\Http\ExceptionHandler
     */
    public function shouldRender(bool $render = true): self
    {
        $this->render = $render;

        return $this;
    }

    /**
     * Set whether the errors should be handled as exceptions.
     *
     * @param bool $errors
     * @return \Quanta\Http\ExceptionHandler
     */
    public function shouldHandleErrors(bool $errors = true): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Set the last exception handler.
     *
     * @param callable $handler
     * @return \Quanta\Http\ExceptionHandler
     */
    public function setRenderer(callable $handler): self
    {
        $this->renderer = $handler;

        return $this;
    }

    /**
     * Add an exception handler.
     *
     * @param callable $handler
     * @return \Quanta\Http\ExceptionHandler
     */
    public function addHandler(callable $handler): self
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
        $exs = [];

        foreach ($this->handlers as $handler) {
            try { $handler($e); }
            catch (\Throwable $ex) { $exs[] = $ex; }
        }

        if ($this->render) {
            try { ($this->renderer)($e, ...$exs); }
            catch (\Throwable $e) { echo (string) $e; }
        } else {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

            if (ExceptionHandler\Utils::shouldEmitJson($accept)) {
                header('application/json');
                echo ExceptionHandler\Utils::json();
            } else {
                header('text/html');
                echo ExceptionHandler\Utils::html('blank.php');
            }
        }
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
        if ($this->errors && ($errno & error_reporting()) > 0) {
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
        if ($this->errors) {
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
