<?php

use Logger\LoggerService;

class Logonbox_Authenticator_Logger implements LoggerService
{
    private $debug = true;

    public function info($message)
    {
	    $this->app_error_log(($this->debug ? "Debug:" : "Info:") . $message);
    }

    public function error($message, $exception)
    {
	    $this->app_error_log("Error:" . $message);
    }

    public function enableDebug($debug)
    {
        $this->debug = $debug;
    }

    public function isDebug()
    {
        return $this->debug;
    }

    private function app_error_log($txt)
    {
        error_log($txt);
    }
}