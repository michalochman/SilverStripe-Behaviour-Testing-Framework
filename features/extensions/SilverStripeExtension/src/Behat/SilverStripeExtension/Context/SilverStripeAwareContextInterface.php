<?php

namespace Behat\SilverStripeExtension\Context;

/*
 * This file is part of the Behat/SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SilverStripe aware interface for contexts.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
interface SilverStripeAwareContextInterface
{
    /**
     * Sets SilverStripe instance.
     *
     * @param String $session_key testSessionKey used in TEMP_FOLDER/testsessions/<testSessionKey> file
     */
    public function setSessionKey($session_key);
}