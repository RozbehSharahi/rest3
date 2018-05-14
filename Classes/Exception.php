<?php

namespace RozbehSharahi\Rest3;

use Throwable;

class Exception extends \Exception
{
    /**
     * @var array
     */
    protected $headers = [
        'Content-Type' => 'application/json'
    ];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Status code on JSON API
     *
     * On json rest api the status is spread over the error items. Therefor this has no setter for changing the
     * main status code, since it should always be 400.
     *
     * @var int
     */
    protected $statusCode = 400;

    /**
     * @return Exception
     */
    static public function create()
    {
        return new static();
    }

    /**
     * Exception constructor.
     *
     * @param string $message This will not used on this rest3 !!
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return Exception
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return Exception
     */
    public function reset()
    {
        $this->errors = [];
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $detail
     * @param int $status
     * @return Exception
     */
    public function addError(string $detail, int $status = 400)
    {
        $this->errors[] = [
            'status' => $status,
            'detail' => $detail,
        ];
        return $this;
    }

    /**
     * Json String of errors
     *
     * @string
     */
    public function getErrorJson()
    {
        return json_encode([
            'errors' => $this->errors
        ]);
    }

}
