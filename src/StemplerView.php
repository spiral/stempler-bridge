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
use Spiral\Views\ContextInterface;
use Spiral\Views\Exception\RenderException;
use Spiral\Views\ViewInterface;
use Spiral\Views\ViewSource;

/**
 * Stempler views are executed within global container scope.
 */
abstract class StemplerView implements ViewInterface
{
    /** @var StemplerEngine */
    protected $engine;

    /** @var ViewSource */
    protected $view;

    /** @var ContextInterface */
    protected $context;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param StemplerEngine   $engine
     * @param ViewSource       $view
     * @param ContextInterface $context
     */
    public function __construct(StemplerEngine $engine, ViewSource $view, ContextInterface $context)
    {
        $this->engine = $engine;
        $this->view = $view;
        $this->context = $context;
        $this->container = $engine->getContainer();
    }

    /**
     * @param array      $data
     * @param \Throwable $e
     * @param int        $lineOffset
     * @return RenderException|\Throwable
     */
    protected function mapException(array $data, \Throwable $e, int $lineOffset = 0)
    {
        $sourcemap = $this->engine->makeSourceMap(
            sprintf("%s:%s", $this->view->getNamespace(), $this->view->getName()),
            $this->context
        );

        if ($sourcemap === null) {
            return $e;
        }

        $userStack = [];
        foreach ($sourcemap->getStack($e->getLine() - $lineOffset) as $stack) {
            $userStack[] = [
                'file'     => $stack['file'],
                'line'     => $stack['line'],
                'class'    => static::class,
                'type'     => '->',
                'function' => 'render',
                'args'     => [$data]
            ];

            if ($stack['file'] === $this->view->getFilename()) {
                // no need to jump over root template
                break;
            }
        }

        $e = new RenderException($e);
        $e->setUserTrace($userStack);

        return $e;
    }
}