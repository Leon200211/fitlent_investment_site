<?php
#/

namespace application\exceptions;



// класс исключений
class RouteException extends \Exception
{

    protected $messages;

    use BaseMethodsExceptions; // для метода writeLog

    // конструктор
    public function __construct($message = "", $code = 0)
    {

        parent::__construct($message, $code);

        $this->messages = include 'messages.php';

        // возвращаем сообщение
        $error = $this->getMessage() ?: $this->messages[$this->getCode()];
        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n In line" . $this->getLine() . "\r\n";


        if(isset($this->messages[$this->getCode()])){
            //$this->message = $this->messages[$this->getCode()];
        }

        // запись логов
        $this->writeLog($error);

        // вывод шаблонов ошибок
        http_response_code($code);
        if(file_exists('application/views/errors/' . $code . '.php')){
            require 'application/views/errors/' . $code . '.php';
            exit;
        }else{
            // вывод ошибок через класс Exception
            exit(self::getMessage());
        }



    }

}