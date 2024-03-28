<?php
declare(strict_types=1);

namespace Core\Services;
/**
 * HTTP response that will be sent to the client.
 */
class Response {

    protected $body;
    protected $statusCode;
    protected $headers;

    public function __construct($body = '', $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
   
    public function send()
    {
        $this->sendHeaders();
        $this->sendBody();
    }

    protected function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);
        
        foreach ($this->headers as $header => $value) {
            header("$header: $value");
        }
    }

    protected function sendBody()
    {
        echo $this->body;
    }

    public static function ddd($var): void
    {
        var_dump($var);die;
    }

    public static function setHeaders(): void
    {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }
}