<?php

namespace App\Logging;

use Monolog\Handler\FormattableHandlerInterface;

class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter());
            }
        }
    }
}
