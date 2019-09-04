<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler;

use Psr\Container\ContainerInterface;
use Spiral\Core\ContainerScope;
use Spiral\Views\ViewInterface;

/**
 * Stempler views are executed within global container scope.
 */
final class StemplerView implements ViewInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function render(array $data = []): string
    {
        return ContainerScope::runScope($this->container, function () {

            // todo: need render, need exception handling
            // todo: need other stuff

            return 'OK';
        });
    }
}