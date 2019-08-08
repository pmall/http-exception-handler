<?php

declare(strict_types=1);

namespace Quanta\Http\ExceptionHandler;

final class Utils
{
    /**
     * Return the formatted json body for the given exceptions.
     *
     * @param \Throwable ...$exs
     * @return string
     */
    public static function json(\Throwable ...$exs): string
    {
        $data = [
            'success' => false,
            'code' => 500,
            'message' => 'Server error',
            'data' => count($exs) == 0 ? [] : [
                'exceptions' => array_map(function ($e) {
                    return [
                        'type' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                    ];
                }, $exs),
            ],
        ];

        return ($body = json_encode($data)) ? $body : '';
    }

    /**
     * Return the html body for the given exceptions formatted with the given
     * template.
     *
     * @param string        $template
     * @param \Throwable    ...$exs
     * @return string
     */
    public static function html(string $template, \Throwable ...$exs): string
    {
        $e = array_shift($exs);

        ob_start();
        require implode('/', [__DIR__, '../templates', $template]);
        return ($body = ob_get_clean()) ? $body : '';
    }

    /**
     * Return whether a json response should be emitted based on the given
     * accept header value.
     *
     * @param string $accept
     * @return bool
     */
    public static function shouldEmitJson(string $accept): bool
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
