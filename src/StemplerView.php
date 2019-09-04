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
use Spiral\Stempler\Compiler\SourceMap;
use Spiral\Views\Exception\RenderException;
use Spiral\Views\ViewInterface;

/**
 * Stempler views are executed within global container scope.
 */
abstract class StemplerView implements ViewInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $sourcemap;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array      $data
     * @param \Throwable $e
     * @param int        $lineOffset
     * @return RenderException|\Throwable
     */
    protected function mapException(array $data, \Throwable $e, int $lineOffset = 0)
    {
        $sourcemap = new SourceMap();
        $sourcemap->unserialize($this->sourcemap);

        $stack = $sourcemap->getStack($e->getLine() - $lineOffset);

        foreach ($stack as &$item) {
            $item['class'] = StemplerView::class;
            $item['type'] = '->';
            $item['function'] = 'render';
            $item['args'] = [$data];

            unset($item['grammar'], $item);
        }

        $e = new RenderException($e);
        $e->setUserTrace($stack);

        return $e;
    }
}