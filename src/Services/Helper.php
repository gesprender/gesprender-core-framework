<?php
namespace Core\Services;

class Helper {

    public static function character_invalid(string $string)
    {
        $array_characters_invalid = ['/', "'", '"', '\\'];
        for($i = 0; $i < strlen($string); $i++){
            if(in_array($string[$i], $array_characters_invalid)){
                return true;
            }
        }
        false;
    }

    public static function validate_input(array $input)
    {

        foreach($input as $x){
            $value = $x;
            $permitidos = 'aábcdeéfghiíjklmnoópqrstuúvwxyzAÁBCDEÉFGHIÍJKLMNOÓPQRSTUÚVWXYZ0123456789/@-_. $!¿?¡#%&()=|°:;'; 
            for ($i=0; $i<strlen($value); $i++){ 
                if (strpos($permitidos, substr($value,$i,1))===false){
                    return false;
                }
            }
            continue;
        }
        return true;
    }

    public static function validate_input_only_letters_characters(array $input)
    {
        foreach($input as $x){
            $value = $x;
            $permitidos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890 '; 
            for ($i=0; $i<strlen($value); $i++){ 
                if (strpos($permitidos, substr($value,$i,1))===false){
                    return false;
                }
            }
            return true; 
        }
    }

    public static function Fsize($dir)
    {
        clearstatcache();
        $cont = 0;
        if (is_dir($dir)) {
            if ($gd = opendir($dir)) {
                while (($archivo = readdir($gd)) !== false) {
                    if ($archivo != "." && $archivo != "..") {
                        if (is_dir($archivo)) {
                            $cont += self::Fsize($dir . "/" . $archivo);
                        } else {
                            $cont += sprintf("%u", filesize($dir . "/" . $archivo));
                        }
                    }
                }
                closedir($gd);
            }
        }
        // echo "PESO DE DESCARGAS: 2,68 GB (2.881.723.298 bytes)</br>";
        return self::formatBytes($cont);
    }

    public static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   
    
        return is_nan((float)round(pow(1024, $base - floor($base)), $precision)) ? '0' : round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public static function list_files($directorio)
    {
        $list_files = scandir($directorio);
        return count($list_files) - 2;
    }

    public static function all_unset(array &$values)
    {
        if(count($values) > 0){
            foreach($values as $key => $value){
                unset($values[$key]);
            }
        }
    }

    public static function JsonResponse(array $response)
    {
        echo json_encode($response); die;
    }

    public static function clearSpecialsCharacters( string $character ) : string 
    {
		$character = str_replace("[áàâãªä@]","a",$character);
		$character = str_replace("[ÁÀÂÃÄ]","A",$character);
		$character = str_replace("[éèêë]","e",$character);
		$character = str_replace("[ÉÈÊË]","E",$character);
		$character = str_replace("[íìîï]","i",$character);
		$character = str_replace("[ÍÌÎÏ]","I",$character);
		$character = str_replace("[óòôõºö]","o",$character);
		$character = str_replace("[ÓÒÔÕÖ]","O",$character);
		$character = str_replace("[úùûü]","u",$character);
		$character = str_replace("[ÚÙÛÜ]","U",$character);
		$character = str_replace("[,;.:]","",$character);
		$character = str_replace("['\"]","",$character);
		$character = str_replace("[?¿]","",$character);
		$character = str_replace("[)(]","",$character);
		$character = str_replace("[\[\]]","",$character);
		$character = str_replace("[<>]","",$character);
		$character = str_replace("[-_]","",$character);
		$character = str_replace("[!¡]","",$character);
		$character = str_replace("[{}]","",$character);
		$character = str_replace("[%#$&/|°¬]","",$character);
		return $character;
	}

    public static function getMonthName($mes)
    {
        switch ($mes) {
            case '01':
                return 'Enero';
            case '02':
                return 'Febrero';
            case '03':
                return 'Marzo';
            case '04':
                return 'Abril';
            case '05':
                return 'Mayo';
            case '06':
                return 'Junio';
            case '07':
                return 'Julio';
            case '08':
                return 'Agosto';
            case '09':
                return 'Septiembre';
            case '10':
                return 'Octubre';
            case '11':
                return 'Noviembre';
            case '12':
                return 'Disciembre';
            default:
                return false;
        }
    }

    public static function nombreDia($num)
    {
        switch ($num) {
            case 1:
                return 'Lunes';
            case 2:
                return 'Martes';
            case 3:
                return 'Miercoles';
            case 4:
                return 'Jueves';
            case 5:
                return 'Viernes';
            case 6:
                return 'Sabado';
            case 7:
                return 'Domingo';
            default:
                return false;
        }
    }

    public static function letterWithNumber($let)
    {
        $letters = "ABCDEFGHIJKLMNOPQRSTUVWHYZ";
        $lettersMin = "abcdefghijklmnopqrstuvwxyz";
        $n = 0;
        for ($i = 0; $i < strlen($let); $i++) {
            if ($i + 1 < strlen($let)) {
                $n += 25;
            }
            for ($x = 0; $x < strlen($letters); $x++) {
                if ($let[$i] == $letters[$x] || $let[$i] == $lettersMin[$x]) {
                    $n += $x + 1;
                }
            }
        }
        return $n;
    }
}