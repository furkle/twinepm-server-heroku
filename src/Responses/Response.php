<?php
namespace TwinePM\Responses;

use \TwinePM\Errors\ErrorInfo;
use \Exception;
/* A class used to pass data up from methods back to the endpoint. Also
 * responsible for dispatching to the logger. */
class Response implements IResponse {
    public function __construct(int $status = 200, string $error = null) {
        $this->status = $status;
        $this->error = $error;
    }

    public function getPayload(): array {
        $payload = $this->toArray();
        foreach($payload as $key => $value) {
            if (preg_match("/^(error|errorCode|errorData|status)$/", $key)) {
                $props[$key] = $value;
            }
        }

        return $payload;
    }

    public function getOutput() {
        if (isset($this->plainMessage)) {
            return $this->plainMessage;
        } else {
            $output = $this->toArray();
            foreach($output as $key => $value) {
                if (($key === "error" and !$value) or
                    ($key === "errorCode" and !$value) or
                    $key === "errorData")
                {
                    unset($output[$key]);
                }
            }

            return $output;
        }
    }

    public function toArray(): array {
        $vars = get_object_vars($this);
        return $this->toArrayRecurse($vars);
    }

    private function toArrayRecurse(array &$arr): array {
        if (gettype($arr) === "array" or $arr instanceof Traversable) {
            foreach ($arr as $key => $value) {
                $type = gettype($value);
                if ($type === "object" and method_exists($value, "toArray")) {
                    $arr[$key] = $value->toArray();
                } else if ($type === "array" or $value instanceof Traversable) {
                    $arr[$key] = $this->toArrayRecurse($value);
                }
            }
        }

        return $arr;
    }

    public function toJson(): string {
        return json_encode($this->toArray());
    }

    public function isError(): bool {
        return $this->error or $this->status !== static::HTTP_SUCCESS;
    }

    public function getError(): ?ErrorResponse {
        if (!$this->isError()) {
            return null;
        }

        $errorCode = isset($this->errorCode) ? $this->errorCode : null;
        if (!$errorCode) {
            $errorCode = "NoCodeProvided";
        }

        $errorData = isset($this->errorData) ? $this->errorData : null;

        $error = new ErrorResponse($errorCode, $errorData);
        return $error;
    }
}