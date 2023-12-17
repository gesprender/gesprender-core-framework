<?php

class PagesDefault
{
    public const Styles = '
    <style type="text/css">
        body { margin: 0; padding: 0;font-family: "Arial", sans-serif; background-color: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; }
        .container { max-width: 600px; padding: 20px; }
        h1 { font-size: 100px; margin: 0; }
        p { font-size: 20px; }
        a { color: #4caf50; text-decoration: none; font-size: 18px; border: 1px solid #4caf50; padding: 20px; border-radius: 5px; transition: background-color 0.3s, color 0.3s; }
        a:hover { background-color: #4caf50; color: #121212; }
    </style>
    ';
    public static function CoreError($description, $title = 'Error'): string
    {
        return self::Styles . ' 
        <body><div class="container"><h1>'.$title.'</h1><p>'.$description.'</p></div></body>       
        ';
    }
}
