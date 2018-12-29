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

namespace Dkarlovi\Transmailifier\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterCommandsCompilerPass implements CompilerPassInterface
{
    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function process(ContainerBuilder $builder): void
    {
        $commands = $builder->findTaggedServiceIds('console.command');
        foreach ($commands as $id => $arguments) {
            $definition = $builder->getDefinition($id);

            $className = $definition->getClass();
            if (null === $className) {
                continue;
            }

            if (0 !== mb_strpos($className, 'Dkarlovi\\Transmailifier')) {
                $builder->removeDefinition($id);
            }
        }
    }
}
