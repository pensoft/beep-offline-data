<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Psr\Log\LoggerInterface;

class APIsController extends Controller
{
    protected LoggerInterface $log;

    protected array $errors = [];
    protected int   $responseStatus;

    /**
     * APIsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @param string $error
     */
    public function addError(string $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return int
     */
    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    /**
     * @param int $responseStatus
     */
    public function setResponseStatus(int $responseStatus): void
    {
        $this->responseStatus = $responseStatus;
    }
}
