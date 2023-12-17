<?php
namespace Config\Commands;

abstract class AbstractConsoleLibrary 
{
    const STD_IN = 'php://stdin';
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";

    public function std_in()
    {
        return fopen(self::STD_IN, 'r');
    }

    public static function colorText($text, $colorCode = self::WHITE) {
        return "\033[" . $colorCode . "" . $text . "\033[0m";
    }

}