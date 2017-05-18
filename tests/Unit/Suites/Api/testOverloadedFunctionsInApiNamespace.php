<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

function parse_url($url, $component = -1)
{
    if (ApiTestOverloadedPhpFunctions::$simulateFailureParsingUrl) {
        return false;
    }
    return \parse_url($url, $component);
}

function file_get_contents($filename, $flags = null, $context = null)
{
    if (null !== ApiTestOverloadedPhpFunctions::$simulateHttpResponseBody) {
        return ApiTestOverloadedPhpFunctions::$simulateHttpResponseBody;
    }
    return \file_get_contents($filename, $flags, $context);
}

function stream_context_create(array $options = null, array $params = null)
{
    ApiTestOverloadedPhpFunctions::$streamContextOptionsSpy = $options;
    return \stream_context_create($options, $params);
}

class ApiTestOverloadedPhpFunctions
{
    public static $simulateFailureParsingUrl = false;
    public static $simulateHttpResponseBody = null;
    public static $streamContextOptionsSpy = null;

    public static function clear()
    {
        self::$simulateFailureParsingUrl = false;
        self::$simulateHttpResponseBody = null;
        self::$streamContextOptionsSpy = null;
    }
}
