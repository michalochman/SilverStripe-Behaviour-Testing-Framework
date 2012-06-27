<?php

namespace Behat\SilverStripeExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface,
    Behat\Behat\Context\ContextInterface;

use Behat\SilverStripeExtension\Context\SilverStripeAwareContextInterface;

/*
 * This file is part of the Behat/SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SilverStripe aware contexts initializer.
 * Sets SilverStripe instance to the SilverStripeAware contexts.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
class SilverStripeAwareInitializer implements InitializerInterface
{
    private $silverstripe;
    private $arg2;

    /**
     * Initializes initializer.
     */
    public function __construct($silverstripe, $arg2)
    {
        $this->silverstripe = $silverstripe;
        $this->arg2 = $arg2;
    }

    /**
     * Checks if initializer supports provided context.
     *
     * @param ContextInterface $context
     *
     * @return Boolean
     */
    public function supports(ContextInterface $context)
    {
        return $context instanceof SilverStripeAwareContextInterface;
    }

    /**
     * Initializes provided context.
     *
     * @param ContextInterface $context
     */
    public function initialize(ContextInterface $context)
    {
        $context->setSilverstripe($this->silverstripe, $this->arg2);
    }
}