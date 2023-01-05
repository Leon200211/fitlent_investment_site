<?php


namespace application\exceptions;


trait BaseMethodsExceptions
{

    // записываем логи
    protected function writeLog($message, $file = 'log.txt', $event = 'Fault'){

        $dataTime = new \DateTime();

        $str = $event . ': ' . $dataTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";

        file_put_contents(dirname(__FILE__) . '/errors_logs/' . $file, $str, FILE_APPEND);

    }



}