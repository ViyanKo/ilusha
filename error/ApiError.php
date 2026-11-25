<?php 
class ApiError extends RuntimeException
{
    private int $retryAfter;

    public function __construct(string $msg, int $status, int $retryAfter = 0)
    {
        parent::__construct($msg, $status);
        $this->retryAfter = $retryAfter;
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function sendResponse(): void
    {
        http_response_code($this->getStatusCode());

        $error = $this;
        $message = $this->getMessage();
        $status = $this->getStatusCode();

        require __DIR__ . "/../public/pages/error.php";
    }

    public static function handle(Throwable $e): void
    {
        $error = $e instanceof self ? $e : new self( 'Неожиданная ошибка', 500);

        if ($error->getStatusCode() >= 500) {
            error_log(sprintf(
                "%s: %s in %s:%d\n%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));
        }

        $error->sendResponse();
    }
}
?>