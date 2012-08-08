<?php

namespace Behat\SilverStripeExtension\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/*
 * This file is part of the Behat\SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Behat\SilverStripe container compilation pass.
 * Passes Base URL available in MinkExtension config.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
class MinkExtensionBaseUrlPass implements CompilerPassInterface
{
    /**
     * Passes MinkExtension's base_url parameter
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('behat.mink')) {
            throw new \Exception('MinkExtension not defined');
        }
        if (!$container->hasParameter('behat.mink.base_url')) {
            throw new \Exception('MinkExtension improperly configured. Missing base_url parameter.');
        }
        $base_url = $container->getParameter('behat.mink.base_url');
        if (empty($base_url)) {
            throw new \Exception('MinkExtension improperly configured. Missing or empty base_url parameter.');
        }
        $container->setParameter('behat.silverstripe_extension.framework_host', $base_url);
    }
}
