<?php

namespace App\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class JsonFormatter extends NormalizerFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $level = strtolower($record->level->name);

        $mappedLevel = match ($level) {
            'debug', 'info', 'notice' => 'info',
            'warning' => 'warn',
            'error', 'critical', 'alert', 'emergency' => 'error',
            default => 'info',
        };

        $output = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.v\Z'),
            'level' => $mappedLevel,
            'requestId' => $record->context['requestId'] ?? $record->extra['requestId'] ?? 'INTERNAL',
            'method' => $record->context['method'] ?? $record->extra['method'] ?? 'INTERNAL',
            'path' => $record->context['path'] ?? $record->extra['path'] ?? 'INTERNAL',
            'message' => $record->message,
        ];

        $context = $record->context;
        $exception = $context['exception'] ?? null;

        // Add error object for error level or if an exception is present
        if ($mappedLevel === 'error' || $exception instanceof \Throwable) {
            if ($exception instanceof \Throwable) {
                $output['error'] = [
                    'code' => $context['error_code'] ?? 'INTERNAL_ERROR',
                    'details' => $exception->getMessage(),
                    'stack' => substr($exception->getTraceAsString(), 0, 1000),
                ];
            } elseif (isset($context['error'])) {
                $output['error'] = $context['error'];
            }
        }

        // Remove known top-level fields from context before moving the rest to attributes
        unset($context['requestId'], $context['method'], $context['path'], $context['exception'], $context['error_code']);

        if (!empty($context)) {
            $output['attributes'] = $context;
        }

        return $this->toJson($output) . "\n";
    }
}
