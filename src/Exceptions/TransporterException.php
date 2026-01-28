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

    public static function throwIfMissingAppKey($appKey = null)
    {
        if (empty($appKey)) {
            throw new self('Missing App Key (NADI_APP_KEY). This is the application identifier token.');
        }
    }

    public static function throwIfMissingAppCredentials($apiKey = null, $appKey = null)
    {
        self::throwIfMissingApiKey($apiKey);
        self::throwIfMissingAppKey($appKey);
    }
}
