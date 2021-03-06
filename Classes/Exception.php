<?php

namespace RozbehSharahi\Rest3;

use Throwable;

class Exception extends \Exception
{
    /**
     * Debug Mode
     *
     * This will set full debug mode and cause the first add error call to
     * throw an exception not of Type RozbehSharahi\Rest3\Exception instead
     * \Exception.
     *
     * This way the exception is not caught by Rest3 and rendered as JSON response.
     *
     * Specially useful for testing
     *
     * @var bool
     */
    protected static $debugMode = false;

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
     * @param bool $debugMode
     */
    static public function setDebugMode(bool $debugMode)
    {
        static::$debugMode = $debugMode;
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
     * @return array
     */
    public function getPayload()
    {
        return ['errors' => $this->getErrors()];
    }

    /**
     * @param string $detail
     * @param int $status
     * @return $this
     * @throws \Exception
     */
    public function addError(string $detail, int $status = 400)
    {
        if (static::$debugMode) {
            throw new \Exception($detail);
        }
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
