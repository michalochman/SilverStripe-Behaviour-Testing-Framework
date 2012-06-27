<?php

namespace SilverStripe\Test\Behaviour;

use Behat\Behat\Context\Step;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Driver\GoutteDriver,
    Behat\Mink\Driver\Selenium2Driver,
    Behat\Mink\Exception\UnsupportedDriverActionException;

// Mink etc.
require_once 'vendor/autoload.php';

/**
 * SilverStripeContext
 *
 * Generic context wrapper used as a base for Behat FeatureContext.
 */
class SilverStripeContext extends MinkContext
{
    /**
     * Parses given URL and returns its components
     *
     * @param $url
     * @return array|mixed Parsed URL
     */
    public function parseUrl($url)
    {
        $url = parse_url($url);
        $url['vars'] = array();
        if (!isset($url['fragment'])) {
            $url['fragment'] = null;
        }
        if (isset($url['query'])) {
            parse_str($url['query'], $url['vars']);
        }

        return $url;
    }

    /**
     * Checks whether current URL is close enough to the given URL.
     * Unless specified in $url, get vars will be ignored
     * Unless specified in $url, fragment identifiers will be ignored
     *
     * @param $url string URL to compare to current URL
     * @return boolean Returns true if the current URL is close enough to the given URL, false otherwise.
     */
    public function isCurrentUrlSimilarTo($url)
    {
        $current = $this->parseUrl($this->getSession()->getCurrentUrl());
        $test = $this->parseUrl($url);

        if ($current['path'] !== $test['path']) {
            return false;
        }

        if (isset($test['fragment']) && $current['fragment'] !== $test['fragment']) {
            return false;
        }

        foreach ($test['vars'] as $name => $value) {
            if (!isset($current['vars'][$name]) || $current['vars'][$name] !== $value) {
                return false;
            }
        }

        return true;
    }

    public function canIntercept()
    {
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof GoutteDriver) {
            return true;
        }
        else {
            if ($driver instanceof Selenium2Driver) {
                return false;
            }
        }

        throw new UnsupportedDriverActionException('You need to tag the scenario with "@mink:goutte" or "@mink:symfony". Intercepting the redirections is not supported by %s', $driver);
    }

    /**
     * @Given /^(.*) without redirection$/
     */
    public function theRedirectionsAreIntercepted($step)
    {
        if ($this->canIntercept()) {
            $this->getSession()->getDriver()->getClient()->followRedirects(false);
        }

        return new Step\Given($step);
    }
}
