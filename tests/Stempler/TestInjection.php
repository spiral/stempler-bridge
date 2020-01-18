<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Stempler\Tests;

class TestInjection
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
