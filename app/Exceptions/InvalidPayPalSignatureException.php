<?php

namespace App\Exceptions;

use RuntimeException;

/**
2:  * Excepción lanzada cuando la firma de un webhook de PayPal es inválida.
3:  */
class InvalidPayPalSignatureException extends RuntimeException
{
    //
}
