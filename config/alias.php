<?php




function ddd($var, $json_flag = false){
    if($json_flag){
        echo json_encode($var); die;
    }
    var_dump($var);
    die;
}
