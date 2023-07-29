<?php

namespace Nadi\Exceptions;

class TypeException extends \Exception
{
    public static function invalid()
    {
        throw new self('Invalid Type');
    }
}
