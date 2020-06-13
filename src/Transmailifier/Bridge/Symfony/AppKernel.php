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

namespace Dkarlovi\Transmailifier\Bridge\Symfony;

use Dkarlovi\Transmailifier\Bridge\Symfony\DependencyInjection\RegisterCommandsCompilerPass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Xezilaires\Bridge\Symfony\XezilairesBundle;

/**
 * @internal
 */
final class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new XezilairesBundle(),
        ];
    }

    /**
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/../../../../config/services.yaml');
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return __DIR__.'/../../../../var/cache/prod';
    }

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $builder): void
    {
        $builder->addCompilerPass(new RegisterCommandsCompilerPass());
    }
}
