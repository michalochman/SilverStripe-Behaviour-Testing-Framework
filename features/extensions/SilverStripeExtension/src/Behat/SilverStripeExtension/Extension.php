<?php

namespace Behat\SilverStripeExtension;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Behat\Behat\Extension\Extension as BaseExtension;

/*
 * This file is part of the Behat\SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SilverStripe extension for Behat class.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
class Extension extends BaseExtension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $config    Extension configuration hash (from behat.yml)
     * @param ContainerBuilder $container ContainerBuilder instance
     */
    public function load(array $config, ContainerBuilder $container)
    {
        if (!isset($config['framework_path'])) {
            throw new \InvalidArgumentException('Specify `framework_path` parameter for silverstripe_extension');
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('silverstripe.yml');

        $behat_base_path = $container->getParameter('behat.paths.base');
        $config['framework_path'] = realpath(sprintf('%s%s%s',
            rtrim($behat_base_path, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            ltrim($config['framework_path'], DIRECTORY_SEPARATOR)
        ));
        if (!file_exists($config['framework_path']) || !is_dir($config['framework_path'])) {
            throw new \InvalidArgumentException('Path specified as `framework_path` either doesn\'t exist or is not a directory');
        }

        $container->setParameter('behat.silverstripe_extension.framework_path', $config['framework_path']);
        if (isset($config['ajax_steps'])) {
            $container->setParameter('behat.silverstripe_extension.ajax_steps', $config['ajax_steps']);
        }
    }

    /**
     * Returns compiler passes used by SilverStripe extension.
     *
     * @return array
     */
    public function getCompilerPasses()
    {
        return array(
            new Compiler\MinkExtensionBaseUrlPass(),
        );
    }
}
