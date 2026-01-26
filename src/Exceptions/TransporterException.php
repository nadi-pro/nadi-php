<?php

namespace Nadi\Exceptions;

class TransporterException extends \Exception
{
    public static function throwIfMissingApiKey($apiKey = null)
    {
        if (empty($apiKey)) {
            throw new self('Missing API Key (NADI_API_KEY). This is the Sanctum personal access token.');
        }
    }

    public static function throwIfMissingToken($token = null)
    {
        if (empty($token)) {
            throw new self('Missing Token (NADI_TOKEN). This is the application identifier token.');
        }
    }

    public static function throwIfMissingAppCredentials($apiKey = null, $token = null)
    {
        self::throwIfMissingApiKey($apiKey);
        self::throwIfMissingToken($token);
    }
}
