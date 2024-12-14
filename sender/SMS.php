<?php

class SMS{

    private $message;
    private $destination;

    function __construct($message,$destination){

        $this->message = $message;
        $this->destination = $destination;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getDestination()
    {
        return $this->destination;
    }

}

?>