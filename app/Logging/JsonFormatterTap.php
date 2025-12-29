<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

class JsonFormatterTap
{
    /**
     * Customize the Monolog handlers to use a compact JSON formatter suitable for Loki.
     */
    public function __invoke(Logger $logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
            // Use options that keep JSON readable in Grafana Loki
            $formatter->setJsonEncodeOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $handler->setFormatter($formatter);
        }
    }
}
