<?php

declare(strict_types=1);

namespace Quanta\Http;

final class JsonExceptionRenderer implements ExceptionHandlerInterface
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
        $data = [
            'success' => false,
            'code' => 500,
            'message' => 'Server error',
        ];

        if ($this->debug) {
            $data['exception'] = [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        http_response_code(500);

        header('content-type: application/json');

        echo ($body = json_encode($data)) ? $body : '';
    }
}
