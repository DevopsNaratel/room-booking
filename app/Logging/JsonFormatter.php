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
        $normalized = $this->normalize($record);

        $output = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.v\Z'),
            'level' => match (true) {
                in_array($record->level->name, ['DEBUG', 'INFO', 'NOTICE']) => 'info',
                $record->level->name === 'WARNING' => 'warn',
                in_array($record->level->name, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']) => 'error',
                default => 'info',
            },
            'requestId' => $record->context['requestId'] ?? $record->extra['requestId'] ?? 'INTERNAL',
            'method' => $record->context['method'] ?? $record->extra['method'] ?? 'INTERNAL',
            'path' => $record->context['path'] ?? $record->extra['path'] ?? 'INTERNAL',
            'message' => $record->message,
        ];

        // Add attributes if they exist and are not already in the main output
        $attributes = array_diff_key($record->context, array_flip(['requestId', 'method', 'path', 'exception']));
        if (!empty($attributes)) {
            $output['attributes'] = $attributes;
        }

        // Handle error field for error level
        if ($record->level->name === 'ERROR' || isset($record->context['exception'])) {
            $exception = $record->context['exception'] ?? null;
            if ($exception instanceof \Throwable) {
                $output['error'] = [
                    'code' => $record->context['error_code'] ?? 'INTERNAL_ERROR',
                    'details' => $exception->getMessage(),
                    'stack' => substr($exception->getTraceAsString(), 0, 1000),
                ];
            } elseif (isset($record->context['error'])) {
                $output['error'] = $record->context['error'];
            }
        }

        // Remove requestId/method/path from attributes if they were moved to top level
        unset($output['attributes']['requestId'], $output['attributes']['method'], $output['attributes']['path']);
        if (empty($output['attributes'])) {
            unset($output['attributes']);
        }

        return $this->toJson($output) . "\n";
    }
}
