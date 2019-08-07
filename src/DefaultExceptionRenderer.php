<?php

declare(strict_types=1);

namespace Quanta\Http;

final class DefaultExceptionRenderer implements ExceptionHandlerInterface
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
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if ($this->shouldEmitJson($accept)) {
            (new JsonExceptionRenderer($this->debug))($e);
        } else {
            (new HtmlExceptionRenderer($this->debug))($e);
        }
    }

    /**
     * Return whether a json response should be emitted based on the given
     * accept header value.
     *
     * @param string $accept
     * @return bool
     */
    private function shouldEmitJson(string $accept): bool
    {
        $types = [];

        $sanitized = strtolower(str_replace(' ', '', $accept));

        $entries = explode(',', $sanitized);

        foreach ($entries as $entry) {
            $parts = explode(';q=', $entry);

            if (in_array($parts[0], ['text/html', 'application/json'])) {
                $types[$parts[0]] = count($parts) == 1 ? 1 : (float) $parts[1];
            }
        }

        arsort($types);

        return count($types) > 0 && key($types) == 'application/json';
    }
}
