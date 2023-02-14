<?php

namespace WHMCS\Module\Addon\Netgsm\Client;

class ClientDispatcher {

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return array
     */
    public function dispatch($action, $vars)
    {
        if (!$action) {
            $action = 'index';
        }

        $controller = new Controller();

        if (is_callable(array($controller, $action))) {
            return $controller->$action($vars);
        }
    }
}
