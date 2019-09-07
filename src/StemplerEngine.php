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
use Spiral\Stempler\Compiler\Renderer\CoreRenderer;
use Spiral\Stempler\Compiler\Renderer\HTMLRenderer;
use Spiral\Stempler\Compiler\Renderer\PHPRenderer;
use Spiral\Stempler\Compiler\Result;
use Spiral\Stempler\Compiler\SourceMap;
use Spiral\Stempler\Directive\DirectiveInterface;
use Spiral\Stempler\Finalizer\TrimLines;
use Spiral\Stempler\Lexer\Grammar\DynamicGrammar;
use Spiral\Stempler\Lexer\Grammar\HTMLGrammar;
use Spiral\Stempler\Lexer\Grammar\InlineGrammar;
use Spiral\Stempler\Lexer\Grammar\PHPGrammar;
use Spiral\Stempler\Parser\Syntax\DynamicSyntax;
use Spiral\Stempler\Parser\Syntax\HTMLSyntax;
use Spiral\Stempler\Parser\Syntax\InlineSyntax;
use Spiral\Stempler\Parser\Syntax\PHPSyntax;
use Spiral\Stempler\Transform\Finalizer\DynamicToPHP;
use Spiral\Stempler\Transform\Finalizer\StackCollector;
use Spiral\Stempler\Transform\Merge\ExtendsParent;
use Spiral\Stempler\Transform\Merge\ResolveImports;
use Spiral\Stempler\Transform\Visitor\DefineAttributes;
use Spiral\Stempler\Transform\Visitor\DefineBlocks;
use Spiral\Stempler\Transform\Visitor\DefineHidden;
use Spiral\Stempler\Transform\Visitor\DefineStacks;
use Spiral\Views\ContextInterface;
use Spiral\Views\EngineInterface;
use Spiral\Views\Exception\CompileException;
use Spiral\Views\Exception\EngineException;
use Spiral\Views\LoaderInterface;
use Spiral\Views\ProcessorInterface;
use Spiral\Views\ViewInterface;
use Spiral\Views\ViewSource;

final class StemplerEngine implements EngineInterface
{
    public const EXTENSION = 'dark.php';

    /** @var string */
    private $classPrefix = '__StemplerView__';

    /** @var ContainerInterface */
    private $container;

    /** @var Builder */
    private $builder;

    /** @var StemplerCache|null */
    private $cache;

    /** @var LoaderInterface|null */
    private $loader;

    /** @var ProcessorInterface[] */
    private $processors = [];

    /** @var DirectiveInterface[] */
    private $directives = [];

    /**
     * @param ContainerInterface $container
     * @param StemplerCache|null $cache
     * @param array              $processors
     * @param array              $directives
     */
    public function __construct(
        ContainerInterface $container,
        StemplerCache $cache = null,
        array $processors = [],
        array $directives = []
    ) {
        $this->container = $container;
        $this->cache = $cache;

        $this->processors = $processors;
        $this->directives = $directives;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function withLoader(LoaderInterface $loader): EngineInterface
    {
        $engine = clone $this;
        $engine->loader = $loader->withExtension(static::EXTENSION);

        $builder = new Builder(new StemplerLoader($engine->loader, $this->processors));

        // we are using fixed set of grammars and renderers for now
        $builder->getParser()->addSyntax(new PHPGrammar(), new PHPSyntax());
        $builder->getParser()->addSyntax(new InlineGrammar(), new InlineSyntax());
        $builder->getParser()->addSyntax(new DynamicGrammar(), new DynamicSyntax());
        $builder->getParser()->addSyntax(new HTMLGrammar(), new HTMLSyntax());

        $builder->getCompiler()->addRenderer(new CoreRenderer());
        $builder->getCompiler()->addRenderer(new PHPRenderer());
        $builder->getCompiler()->addRenderer(new HTMLRenderer());

        // AST modifications
        $builder->addVisitor(new DefineBlocks(), Builder::STAGE_PREPARE);
        $builder->addVisitor(new DefineAttributes(), Builder::STAGE_PREPARE);
        $builder->addVisitor(new DefineHidden(), Builder::STAGE_PREPARE);
        $builder->addVisitor(new DefineStacks(), Builder::STAGE_PREPARE);

        // template transformation
        $builder->addVisitor(new ResolveImports($builder), Builder::STAGE_TRANSFORM);
        $builder->addVisitor(new ExtendsParent($builder), Builder::STAGE_TRANSFORM);

        // php conversion
        $builder->addVisitor(new StackCollector(), Builder::STAGE_FINALIZE);
        $builder->addVisitor(
            new DynamicToPHP(DynamicToPHP::DEFAULT_FILTER, $this->directives),
            Builder::STAGE_FINALIZE
        );

        // smaller views
        $builder->addVisitor(new TrimLines(), Builder::STAGE_FINALIZE);

        $engine->builder = $builder;

        return $engine;
    }

    /**
     * @inheritDoc
     */
    public function getLoader(): LoaderInterface
    {
        if ($this->loader === null) {
            throw new EngineException("No associated loader found");
        }

        return $this->loader;
    }

    /**
     * Return builder locked to specific context.
     *
     * @param ContextInterface $context
     * @return Builder
     */
    public function getBuilder(ContextInterface $context): Builder
    {
        if ($this->builder === null) {
            throw new EngineException("No associated builder found");
        }

        // since view source support pre-processing we must ensure that context is always set
        $this->builder->getLoader()->setContext($context);

        return $this->builder;
    }

    /**
     * @inheritDoc
     */
    public function compile(string $path, ContextInterface $context): ViewInterface
    {
        // for name generation only
        $view = $this->getLoader()->load($path);

        // expected template class name
        $class = $this->className($view, $context);

        // cache key
        $key = $this->cacheKey($view, $context);

        if ($this->cache !== null && $this->cache->isFresh($key)) {
            $this->cache->load($key);
        } elseif (!class_exists($class)) {
            try {
                $result = $this->getBuilder($context)->compile($path);
            } catch (\Throwable $e) {
                throw new CompileException($e);
            }

            $compiled = $this->compileClass($class, $result);

            if ($this->cache !== null) {
                $this->cache->write($key, $compiled, array_map(function ($path) {
                    return $this->getLoader()->load($path)->getFilename();
                }, $result->getPaths()));

                $this->cache->load($key);
            }

            if (!class_exists($class)) {
                // runtime initialization
                eval('?>' . $compiled);
            }
        }

        if (!class_exists($class)) {
            throw new EngineException("Unable to load `{$path}`, cache might be corrupted");
        }

        return new $class($this, $view, $context);
    }

    /**
     * @inheritDoc
     */
    public function reset(string $path, ContextInterface $context)
    {
        if ($this->cache === null) {
            return;
        }

        $source = $this->getLoader()->load($path);

        $this->cache->delete($this->cacheKey($source, $context));
    }

    /**
     * @inheritDoc
     */
    public function get(string $path, ContextInterface $context): ViewInterface
    {
        return $this->compile($path, $context);
    }

    /**
     * Calculate sourcemap for exception highlighting.
     *
     * @param string           $path
     * @param ContextInterface $context
     * @return SourceMap|null
     */
    public function makeSourceMap(string $path, ContextInterface $context): ?SourceMap
    {
        try {
            $builder = $this->getBuilder($context);

            // there is no need to cache sourcemaps since they are used during the exception only
            return $builder->compile($path)->getSourceMap($builder->getLoader());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $class
     * @param Result $result
     * @return string
     */
    private function compileClass(string $class, Result $result): string
    {
        $template = '<?php class %s extends \Spiral\Stempler\StemplerView {            
            public function render(array $data=[]): string {
                ob_start();
                $__outputLevel__ = ob_get_level();

                try {
                    Spiral\Core\ContainerScope::runScope($this->container, function () use ($data) {
                        extract($data, EXTR_OVERWRITE);
                        ?>%s<?php
                    });
                } catch (\Throwable $e) {
                    while (ob_get_level() >= $__outputLevel__) { ob_end_clean(); }
                    throw $this->mapException(8, $e, $data);                    
                } finally {
                    while (ob_get_level() > $__outputLevel__) { ob_end_clean(); }
                }

                return ob_get_clean(); 
            }
        }';

        return sprintf($template, $class, $result->getContent());
    }

    /**
     * @param ViewSource       $source
     * @param ContextInterface $context
     * @return string
     */
    private function className(ViewSource $source, ContextInterface $context): string
    {
        return $this->classPrefix . $this->cacheKey($source, $context);
    }

    /**
     * @param ViewSource       $source
     * @param ContextInterface $context
     * @return string
     */
    private function cacheKey(ViewSource $source, ContextInterface $context): string
    {
        $key = sprintf(
            "%s.%s.%s",
            $source->getNamespace(),
            $source->getName(),
            $context->getID()
        );

        return hash('sha256', $key);
    }
}
