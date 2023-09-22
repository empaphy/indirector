<?php

declare(strict_types=1);

namespace Empaphy\Indirector;

use Exception;

/**
 * Thrown when a dependency of Indirector (like Rector) is not yet available.
 */
class DependencyNotYetAvailableException extends Exception implements UseFallbackThrowable
{
    /**
     * @param  string  $dependency  The name of the dependency that is not yet available.
     */
    public function __construct(string $dependency)
    {
        parent::__construct(sprintf('The dependency "%s" is not yet available.', $dependency));
    }
}
