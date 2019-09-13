<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Stempler\Tests;

use Spiral\Core\Container\Autowire;
use Spiral\Stempler\Bootloader\StemplerBootloader;
use Spiral\Stempler\Config\StemplerConfig;
use Spiral\Stempler\Directive\ConditionalDirective;
use Spiral\Stempler\Directive\ContainerDirective;
use Spiral\Stempler\Directive\JsonDirective;
use Spiral\Stempler\Directive\LoopDirective;
use Spiral\Stempler\Directive\PHPDirective;
use Spiral\Stempler\Directive\RouteDirective;
use Spiral\Views\Processor\ContextProcessor;

class ConfigTest extends BaseTest
{
    public function testWireConfigString()
    {
        $config = new StemplerConfig([
            'processors' => [ContextProcessor::class]
        ]);

        $this->assertInstanceOf(
            ContextProcessor::class,
            $config->getProcessors()[0]->resolve($this->container)
        );
    }

    public function testWireDirective()
    {
        $config = new StemplerConfig([
            'directives' => [ContainerDirective::class]
        ]);

        $this->assertInstanceOf(
            ContainerDirective::class,
            $config->getDirectives()[0]->resolve($this->container)
        );
    }

    public function testWireConfig()
    {
        $config = new StemplerConfig([
            'processors' => [
                new Autowire(ContextProcessor::class)
            ]
        ]);

        $this->assertInstanceOf(
            ContextProcessor::class,
            $config->getProcessors()[0]->resolve($this->container)
        );
    }

    public function testDebugConfig()
    {
        $loader = $this->container->get(StemplerBootloader::class);
        $loader->addDirective(self::class);

        $config = $this->container->get(StemplerConfig::class);

        $this->assertEquals([
            new Autowire(PHPDirective::class),
            new Autowire(RouteDirective::class),
            new Autowire(LoopDirective::class),
            new Autowire(JsonDirective::class),
            new Autowire(ConditionalDirective::class),
            new Autowire(ContainerDirective::class),
            new Autowire(self::class)
        ], $config->getDirectives());
    }
}