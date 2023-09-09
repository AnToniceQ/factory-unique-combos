<?php

namespace App\Exceptions;

use Exception;

class FactoryMaxTriesExceededException extends Exception
{
    public function __construct(string $factoryName, int $maxTries, array $wantedUniqueColumns, $code = 0, Throwable $previous = null) {
        parent::__construct(
            'The unique factory ' . $factoryName . ' has exceeded its maximum ' . $maxTries . ' tries to find
            unique combinations: [' . implode(', ', array_keys($wantedUniqueColumns)) . ']', $code, $previous);
    }
}
