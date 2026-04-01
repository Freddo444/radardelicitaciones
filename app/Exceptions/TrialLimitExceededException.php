<?php

namespace App\Exceptions;

use Exception;

class TrialLimitExceededException extends Exception
{
    public function __construct()
    {
        parent::__construct('Has alcanzado el límite de análisis de tu prueba gratuita. Suscríbete para análisis ilimitados.');
    }
}
