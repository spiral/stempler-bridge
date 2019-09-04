<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Bootloader;

use Psr\Container\ContainerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\Bootloader\DependedInterface;
use Spiral\Bootloader\Views\ViewsBootloader;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Append;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Stempler\Config\StemplerConfig;
use Spiral\Stempler\Directive\ConditionalDirective;
use Spiral\Stempler\Directive\ContainerDirective;
use Spiral\Stempler\Directive\JsonDirective;
use Spiral\Stempler\Directive\LoopDirective;
use Spiral\Stempler\StemplerCache;
use Spiral\Stempler\StemplerEngine;
use Spiral\Translator\Views\LocaleProcessor;
use Spiral\Twig\TwigCache;
use Spiral\Twig\TwigEngine;
use Spiral\Views\Config\ViewsConfig;
use Spiral\Views\Processor\ContextProcessor;

/**
 * Initiates stempler engine, it's cache and directives.
 */
final class StemplerBootloader extends Bootloader implements DependedInterface
{
    const SINGLETONS = [
        StemplerEngine::class => [self::class, 'stemplerEngine']
    ];

    /** @var ConfiguratorInterface */
    private $config;

    /**
     * @param ConfiguratorInterface $config
     */
    public function __construct(ConfiguratorInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param ContainerInterface $container
     * @param ViewsBootloader    $views
     */
    public function boot(ContainerInterface $container, ViewsBootloader $views)
    {
        $this->config->setDefaults('views/stempler', [
            'directives' => [
                LoopDirective::class,
                JsonDirective::class,
                ConditionalDirective::class,
                ContainerDirective::class
            ],
            'processors' => [
                ContextProcessor::class
            ]
        ]);

        $views->addEngine(StemplerEngine::class);

        if ($container->has(LocaleProcessor::class)) {
            $this->addProcessor(LocaleProcessor::class);
        }
    }

    /**
     * @param string $directive
     */
    public function addDirective($directive)
    {
        $this->config->modify('views/stempler', new Append('directives', null, $directive));
    }

    /**
     * @param mixed $processor
     */
    public function addProcessor($processor)
    {
        $this->config->modify('views/stempler', new Append('processors', null, $processor));
    }

    /**
     * @return array
     */
    public function defineDependencies(): array
    {
        return [
            ViewsBootloader::class
        ];
    }

    /**
     * @param ContainerInterface $container
     * @param StemplerConfig     $config
     * @param ViewsConfig        $viewConfig
     * @param FactoryInterface   $factory
     * @return StemplerEngine
     */
    protected function stemplerEngine(
        ContainerInterface $container,
        StemplerConfig $config,
        ViewsConfig $viewConfig,
        FactoryInterface $factory
    ): StemplerEngine {
        $processors = [];
        foreach ($config->getProcessors() as $processor) {
            if ($processor instanceof Autowire) {
                $processor = $processor->resolve($factory);
            }

            $processors[] = $processor;
        }

        $directives = [];
        foreach ($config->getDirectives() as $directive) {
            if ($directive instanceof Autowire) {
                $directive = $directive->resolve($factory);
            }

            $directives[] = $directive;
        }

        $cache = null;
        if ($viewConfig->isCacheEnabled()) {
            $cache = new StemplerCache($viewConfig->getCacheDirectory());
        }

        return new StemplerEngine($container, $cache, $processors, $directives);
    }
}