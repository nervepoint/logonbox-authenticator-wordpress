<?php

use Logger\LoggerService;

class Logonbox_Authenticator_Logger implements LoggerService
{
    private bool $debug = false;

    public function info(string $message)
    {
        error_log(($this->debug ? "Debug:" : "Info:") . $message);
    }

    public function error(string $message, Exception $exception)
    {
        error_log("Error:" . $message);
    }

    public function enableDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }
}