<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Finalizer;

use Spiral\Stempler\Node\HTML\Attr;
use Spiral\Stempler\Node\Raw;
use Spiral\Stempler\VisitorContext;
use Spiral\Stempler\VisitorInterface;

final class TrimLines implements VisitorInterface
{
    /**
     * @inheritDoc
     */
    public function enterNode($node, VisitorContext $ctx)
    {
    }

    /**
     * @inheritDoc
     */
    public function leaveNode($node, VisitorContext $ctx)
    {
        if (!$node instanceof Raw) {
            return null;
        }

        foreach ($ctx->getScope() as $scope) {
            if ($scope instanceof Attr) {
                // do not trim attribute values
                return null;
            }
        }

        $node->content = preg_replace("/([\n\r]+)/", "\n", $node->content);
    }
}