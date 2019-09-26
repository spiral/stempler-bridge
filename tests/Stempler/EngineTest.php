<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Tests;

use Spiral\Views\Context\ValueDependency;
use Spiral\Views\Exception\CompileException;
use Spiral\Views\Exception\RenderException;
use Spiral\Views\ViewContext;

class EngineTest extends BaseTest
{
    public function testList()
    {
        $views = $this->getStempler()->getLoader()->list();

        $this->assertContains('default:test', $views);
        $this->assertContains('other:test', $views);
    }

    public function testRender()
    {
        $s = $this->getStempler();
        $this->assertSame(
            'test',
            $s->get('test', new ViewContext())->render([])
        );

        $this->assertSame(
            'other test',
            $s->get('other:test', new ViewContext())->render([])
        );
    }

    public function testRenderInContext()
    {
        $ctx = new ViewContext();
        $ctx = $ctx->withDependency(new ValueDependency('name', 'Test'));

        $s = $this->getStempler();
        $this->assertSame(
            'hello Anton of Test',
            $s->get('other:ctx', $ctx)->render(['name' => 'Anton'])
        );
    }

    public function testRenderException()
    {
        $twig = $this->getStempler();

        try {
            $twig->get('echo', new ViewContext())->render();
        } catch (RenderException $e) {
            $t = $e->getUserTrace()[0];

            $this->assertSame(2, $t['line']);
            $this->assertContains('echo.dark.php', $t['file']);
        }
    }

    public function testRenderNestedException()
    {
        $twig = $this->getStempler();

        try {
            $twig->get('other:echo-in', new ViewContext())->render();
        } catch (RenderException $e) {
            $t = $e->getUserTrace();
            $this->assertCount(2, $t);

            $this->assertSame(2, $t[0]['line']);
            $this->assertContains('echo.dark.php', $t[0]['file']);

            $this->assertSame(3, $t[1]['line']);
            $this->assertContains('echo-in.dark.php', $t[1]['file']);
        }
    }

    public function testSyntaxException()
    {
        $twig = $this->getStempler();

        try {
            $twig->get('other:bad', new ViewContext());
        } catch (CompileException $e) {
            $this->assertContains('bad.dark.php', $e->getFile());
        }
    }
}
