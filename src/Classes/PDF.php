<?php
namespace Core\Classes;
use Dompdf\Dompdf;

class PDF extends Dompdf
{
    public function __construct()
    {
        parent::__construct();
    }
}