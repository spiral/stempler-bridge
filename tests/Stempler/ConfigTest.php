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
use Spiral\Stempler\Builder;
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

    public function testBootloaderDirective()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addDirective('testBinding');

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(7, $cfg->getDirectives());
        $this->assertSame('test result', $cfg->getDirectives()[6]->resolve($this->container));
    }

    public function testBootloaderProcessors()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addProcessor('testBinding');

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(2, $cfg->getProcessors());
        $this->assertSame('test result', $cfg->getProcessors()[1]->resolve($this->container));
    }

    public function testBootloaderVisitors()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addVisitor('testBinding', Builder::STAGE_FINALIZE);

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(2, $cfg->getVisitors(Builder::STAGE_FINALIZE));
        $this->assertSame('test result', $cfg->getVisitors(Builder::STAGE_FINALIZE)[1]->resolve($this->container));
    }

    public function testBootloaderVisitors0()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addVisitor('testBinding', Builder::STAGE_COMPILE);

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(1, $cfg->getVisitors(Builder::STAGE_COMPILE));
        $this->assertSame('test result', $cfg->getVisitors(Builder::STAGE_COMPILE)[0]->resolve($this->container));
    }

    public function testBootloaderVisitors2()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addVisitor('testBinding', Builder::STAGE_TRANSFORM);

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(1, $cfg->getVisitors(Builder::STAGE_TRANSFORM));
        $this->assertSame('test result', $cfg->getVisitors(Builder::STAGE_TRANSFORM)[0]->resolve($this->container));
    }

    public function testBootloaderVisitors3()
    {
        $this->container->bind('testBinding', function () {
            return 'test result';
        });

        /** @var StemplerBootloader $bootloader */
        $bootloader = $this->container->get(StemplerBootloader::class);

        $bootloader->addVisitor('testBinding', Builder::STAGE_PREPARE);

        /** @var StemplerConfig $cfg */
        $cfg = $this->container->get(StemplerConfig::class);

        $this->assertCount(5, $cfg->getVisitors(Builder::STAGE_PREPARE));
        $this->assertSame('test result', $cfg->getVisitors(Builder::STAGE_PREPARE)[4]->resolve($this->container));
    }
}
