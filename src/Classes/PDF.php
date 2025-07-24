<?php
declare(strict_types=1);

namespace Core\Classes;
use Dompdf\Dompdf;

class PDF extends Dompdf
{
    public function __construct()
    {
        parent::__construct();
    }
}