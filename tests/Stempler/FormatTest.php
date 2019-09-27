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

class FormatTest extends BaseTest
{
    public function testFormatDiv()
    {
        $s = $this->getStempler();

        $this->assertSame(
            "<div>\n  hello\n</div>",
            $s->get('format/f1', new ViewContext())->render([])
        );
    }

    public function testFormatDiv2()
    {
        $s = $this->getStempler();

        $this->assertSame(
            "<div>\n  hello\n</div>",
            $s->get('format/f2', new ViewContext())->render([])
        );
    }

    public function testFormatDiv3()
    {
        $s = $this->getStempler();

        $this->assertSame(
            "<div> first
  <div>
    hello
  </div>
  test
</div>",
            $s->get('format/f3', new ViewContext())->render([])
        );
    }

    public function testFormatDiv4()
    {
        $s = $this->getStempler();

        $this->assertSame(
            "<div>
  hello
  <pre>
          test magic


    </pre>
  extra spaces
</div>",
            $s->get('format/f4', new ViewContext())->render([])
        );
    }
}