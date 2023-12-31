<?php
declare(strict_types=1);

namespace Core\Classes;

class DependencyConstructor 
{

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
            if($_ENV['USE_TYPESCRIPT']){
                $script .= '
                    <script type="module">
                        import RefreshRuntime from "'.HOST_REACT.'/@react-refresh"
                        RefreshRuntime.injectIntoGlobalHook(window)
                        window.$RefreshReg$ = () => {}
                        window.$RefreshSig$ = () => (type) => type
                        window.__vite_plugin_react_preamble_installed__ = true
                    </script>
                ';
                $script .= '<script type="module" src="'.HOST_REACT.'/App.tsx" /></script>';
            }else{
                $script .= '<script type="module" src="'.HOST_REACT.'/App.jsx" /></script>';
            }
            $style = '';
        }

        return '<!-- React dependency -->' .$script . $style;
    }

    public static function BoostrapCDN(): string 
    {
        if($_ENV['USE_BOOSTRAP']){
            return '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">';
        }
    }

    public static function BoostrapLibs(): string 
    {
        if($_ENV['USE_BOOSTRAP']){
            return '<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>';
        }
    }
}



?>