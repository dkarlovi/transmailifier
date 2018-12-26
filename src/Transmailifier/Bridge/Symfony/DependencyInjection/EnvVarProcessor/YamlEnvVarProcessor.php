<?php

declare(strict_types=1);

/*
 * This file is part of the transmailifier project.
 *
 * (c) Dalibor Karlović <dalibor@flexolabs.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Dkarlovi\Transmailifier\Bridge\Symfony\DependencyInjection\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Yaml\Yaml;

class YamlEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        return Yaml::parse($getEnv($name));
    }

    /**
     * {@inheritdoc}
     */
    public static function getProvidedTypes(): array
    {
        return ['yaml' => 'array'];
    }
}
