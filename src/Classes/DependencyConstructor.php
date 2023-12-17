<?php
namespace Core\Classes;
class DependencyConstructor {

    public static function React() : string
    {
        if(MODE == 'Prod' || $_ENV['USE_DIST']){
            $script = '<script type="module" src="./Project/themes/dist/themes.js?v='.VERSION_NUM.'"></script>';
            $style = '<link rel="stylesheet" href="./Project/themes/dist/themes.css?v='.VERSION_NUM.'">';
        }else{
            $pageNotFundReactServer = '
            <style type="text/css">body { margin: 0; padding: 0;font-family: "Arial", sans-serif; background-color: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; }.container { max-width: 600px; padding: 20px; }h1 { font-size: 100px; margin: 0; }p { font-size: 20px; }a { color: #4caf50; text-decoration: none; font-size: 18px; border: 1px solid #4caf50; padding: 20px; border-radius: 5px; transition: background-color 0.3s, color 0.3s; }a:hover { background-color: #4caf50; color: #121212; }</style><body><div class="container"><h1>React error</h1><p>No haz iniciado el servidor. También puedes habilitar la opción "USE_DIST" en .env</p></div></body> 
            ';
            $script = "<script>
                const url = `http://localhost:" .REACT_PORT. "`; // Cambia la URL según sea necesario
                // Realizar una solicitud Fetch a la URL del servidor
                fetch(url)
                .then((response) => {})
                .catch((error) => {
                    document.body.innerHTML = `$pageNotFundReactServer`;
                });</script>
            ";
            $script .= '<script type="module" src="'.HOST_REACT.'/Project/themes/App.jsx" /></script>';
            $style = '';
        }

        return '<!-- React dependency -->' .$script . $style;
    }
}



?>