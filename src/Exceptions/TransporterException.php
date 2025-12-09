<?php

namespace Nadi\Exceptions;

class TransporterException extends \Exception
{
    public static function throwIfMissingAppId($appId = null)
    {
        if (empty($appId)) {
            throw new self('Missing App ID (NADI_APP_ID)');
        }
    }

    public static function throwIfMissingAppSecret($appSecret = null)
    {
        if (empty($appSecret)) {
            throw new self('Missing App Secret (NADI_APP_SECRET)');
        }
    }

    public static function throwIfMissingAppCredentials($appId = null, $appSecret = null)
    {
        self::throwIfMissingAppId($appId);
        self::throwIfMissingAppSecret($appSecret);
    }
}
