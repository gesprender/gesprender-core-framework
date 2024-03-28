<?php
declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\CoreAbstract;

class Request extends CoreAbstract
{
    public $url;
    public $method;
    public $headers;
    public $body;
    public $queryParams;

    public function __construct() 
    {
        $this->url = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = $this->getHeaders();
        $this->body = file_get_contents('php://input');
        $this->queryParams = $_GET;
    }

    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function get($key, $default = false): string|bool|null|int|array
    {
        $value = $default;
        if (isset($this->queryParams[$key])) {
            $value = $this->queryParams[$key];
        } elseif (isset($this->body[$key])) {
            $value = $this->body[$key];
        }
        return $value;
    }

    public static function On(string $key, $callback): void
    {
        $payload = json_decode(file_get_contents("php://input"), true);
        $pathRequest = str_replace("/api/index.php", "", $_SERVER['REQUEST_URI']);
        if(array_key_exists($key, $_REQUEST) || $pathRequest == $key || array_key_exists($key, (array)$payload)){
            $callback();
        }
    }

    public static function getValue($key, $default = false): string|bool|null|int|array
    {
        $payload = json_decode(file_get_contents("php://input"), true);

        if(isset($payload[$key])) return $payload[$key];

        if(isset($_REQUEST[$key])) return $_REQUEST[$key];
        
        return $default;
    }

    public static function getValueByPass($key, $default = false): string|bool|null|int|array
    {
        if(!isset($_REQUEST[$key])) return $default;

        return $_REQUEST[$key];
    }

}