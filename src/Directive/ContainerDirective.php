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

/**
 * Provides the ability to inject services into view code. Can only be used within view object.
 */
final class ContainerDirective extends AbstractDirective
{
    /**
     * Injects service into template.
     *
     * @param Directive $directive
     * @return string
     */
    public function renderInject(Directive $directive): string
    {
        if (count($directive->values) < 2) {
            throw new DirectiveException(
                "Unable to call @inject directive, 2 values required",
                $directive->getContext()
            );
        }

        return sprintf(
            '<?php $%s = $this->container->get(%s); ?>',
            $directive->values[0],
            $directive->values[1]
        );
    }
}