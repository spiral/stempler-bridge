<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Directive;

use Spiral\Stempler\Exception\DirectiveException;
use Spiral\Stempler\Node\Dynamic\Directive;

final class RouteDirective extends AbstractDirective
{
    /**
     * Injects service into template.
     *
     * @param Directive $directive
     * @return string
     */
    public function renderRoute(Directive $directive): string
    {
        if (count($directive->values) < 1) {
            throw new DirectiveException(
                "Unable to call @route directive, at least 1 value is required",
                $directive->getContext()
            );
        }

        return sprintf(
            '<?php echo $this->container->get(\Spiral\Router\RouterInterface::class)->uri(%s); ?>',
            $directive->body
        );
    }
}
