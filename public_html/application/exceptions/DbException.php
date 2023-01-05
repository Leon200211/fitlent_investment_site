<?php


namespace application\exceptions;




// класс исключений
class DbException extends \Exception
{

    protected $messages;

    use BaseMethodsExceptions; // для метода writeLog

    // конструктор
    public function __construct($message = "", $code = 0)
    {

        parent::__construct($message, $code);

        $this->messages = include 'messages.php';

        // возвращаем сообщение
        $error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];
        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n In line" . $this->getLine() . "\r\n";


        if(isset($this->messages[$this->getCode()])){
            $this->message = $this->messages[$this->getCode()];
        }

        // запись логов
        $this->writeLog($error, 'db_log.txt');

    }

}