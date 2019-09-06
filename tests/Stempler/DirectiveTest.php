<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Tests;

use Spiral\Views\ViewContext;

class DirectiveTest extends BaseTest
{
    /**
     * @expectedException \Spiral\Views\Exception\RenderException
     */
    public function testRenderDirectiveEx()
    {
        $s = $this->getStempler();

        $s->get('directive', new ViewContext())->render();
    }

    public function testRenderDirective()
    {
        $s = $this->getStempler();
        $this->container->bind(testInjection::class, new testInjection("abc"));

        $this->assertSame('abc', $s->get('directive', new ViewContext())->render());
    }

    /**
     * @expectedException \Spiral\Views\Exception\CompileException
     */
    public function testBadDirective()
    {
        $s = $this->getStempler();
        $this->container->bind(testInjection::class, new testInjection("abc"));

        $s->get('bad-directive', new ViewContext())->render();
    }
}

class testInjection
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}