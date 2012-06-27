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
        $var_export = 'var_export';
        $debug = <<<DEBUG
Behat\SilverStripeExtension config is:
{$var_export($config, true)}
DEBUG;
        echo $debug . PHP_EOL;

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('silverstripe.yml');
//        $configPath = $container->getParameter('behat.paths.config');
//        var_dump($configPath);

        $container->setParameter('behat.silverstripe_extension.test_argument_second', $config['param1']);
    }
}
