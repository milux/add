<?php
/**
 * Exception for the dispatching process
 *
 * @author Michael Lux <michi.lux@gmail.com>
 * @copyright Copyright (c) 2017 Michael Lux
 * @license LGPLv3
 */

namespace milux\add;


class DispatchException extends \Exception {

    public function sendErrorHeaders() {
        $httpCodes = array(
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found'
        );
        header('HTTP/1.0 ' . $this->getCode() . ' ' . $httpCodes[$this->getCode()]);
        header('X-Error: ' . $this->getMessage());
    }

}