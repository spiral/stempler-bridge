<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Stempler\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Stempler\Exception\ConfigException;

final class StemplerConfig extends InjectableConfig
{
    const CONFIG = "views/stempler";

    /** @var array */
    protected $config = [
        'directives' => [],
        'processors' => []
    ];

    /**
     * @return Autowire[]
     */
    public function getDirectives(): array
    {
        $directives = [];
        foreach ($this->config['directives'] as $directive) {
            if (is_object($directive) && !$directive instanceof Autowire) {
                $directives[] = $directive;
                continue;
            }

            $directives[] = $this->wire($directive);
        }

        return $directives;
    }

    /**
     * @return Autowire[]
     */
    public function getProcessors(): array
    {
        $processors = [];
        foreach ($this->config['processors'] as $processor) {
            if (is_object($processor) && !$processor instanceof Autowire) {
                $processors[] = $processor;
                continue;
            }

            $processors[] = $this->wire($processor);
        }

        return $processors;
    }

    /**
     * @param mixed $item
     * @return Autowire
     *
     * @throws ConfigException
     */
    private function wire($item): Autowire
    {
        if ($item instanceof Autowire) {
            return $item;
        }

        if (is_string($item)) {
            return new Autowire($item);
        }

        throw new ConfigException("Invalid class reference in view config");
    }
}