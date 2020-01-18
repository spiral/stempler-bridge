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
    public function testRenderDirectiveEx(): void
    {
        $s = $this->getStempler();

        $s->get('directive', new ViewContext())->render();
    }

    public function testRenderDirective(): void
    {
        $s = $this->getStempler();
        $this->container->bind(testInjection::class, new testInjection('abc'));

        $this->assertSame('abc', $s->get('directive', new ViewContext())->render());
    }

    public function testRenderDirectiveAsArray(): void
    {
        $s = $this->getStempler();
        $this->container->bind(testInjection::class, new testInjection('abc'));

        $this->assertSame('abc', $s->get('directive2', new ViewContext())->render());
    }

    /**
     * @expectedException \Spiral\Views\Exception\CompileException
     */
    public function testBadDirective(): void
    {
        $s = $this->getStempler();
        $this->container->bind(testInjection::class, new testInjection('abc'));

        $s->get('bad-directive', new ViewContext())->render();
    }

    public function testRouteDirective(): void
    {
        $s = $this->getStempler()->getBuilder(new ViewContext());
        $this->assertSame(
            "<?php echo \$this->container->get(\Spiral\Router\RouterInterface::class)->uri('home', ['action' => 'index']); ?>",
            $s->compile('route')->getContent()
        );
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
