<?php

namespace App\Modules\V1\Trash\Domain\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrashDependenciesException extends ConflictHttpException
{
    /** @param array<string, int> $dependencies */
    public function __construct(
        string $message,
        public readonly array $dependencies,
    ) {
        parent::__construct($message);
    }
}
