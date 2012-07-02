<?php

namespace SilverStripe\Test\Behaviour;

use Behat\Behat\Context\Step,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\SuiteEvent;
use Behat\Gherkin\Node\PyStringNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Driver\GoutteDriver,
    Behat\Mink\Driver\Selenium2Driver,
    Behat\Mink\Exception\UnsupportedDriverActionException;

use Behat\SilverStripeExtension\Context\SilverStripeAwareContextInterface;

// Mink etc.
require_once 'vendor/autoload.php';

/**
 * SilverStripeContext
 *
 * Generic context wrapper used as a base for Behat FeatureContext.
 */
class SilverStripeContext extends MinkContext implements SilverStripeAwareContextInterface
{
    protected $context;
    protected $silverstripe;
    protected $fixtures;
    protected $dbset;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
        $this->context = $parameters;

        $this->dbset = false;
    }

    public function getSession($name = null)
    {
        if (!$this->dbset) {
            $this->setDb();
        }
        return $this->getMink()->getSession($name);
    }

    public function setDb()
    {
        $this->dbset = true;

        $setdb_url = $this->joinUrlParts($this->context['base_url'], '/dev/tests/setdb');
        $setdb_url = sprintf('%s?database=%s', $setdb_url, \DB::get_alternative_database_name());
        $this->getSession()->visit($setdb_url);
    }

    public function setSilverstripe($silverstripe)
    {
        $this->silverstripe = $silverstripe;
    }

    public function getFixture($data_object)
    {
        if (!array_key_exists($data_object, $this->fixtures)) {
            throw new \OutOfBoundsException(sprintf('Data object `%s` does not exist!', $data_object));
        }

        return $this->fixtures[$data_object];
    }

    public function getFixtures()
    {
        return $this->fixtures;
    }

    /**
     * @BeforeSuite
     */
    public static function setup(SuiteEvent $event)
    {

    }

    /**
     * @AfterSuite
     */
    public static function teardown(SuiteEvent $event)
    {
        \SapphireTest::kill_temp_db();
        \DB::set_alternative_database_name(null);
    }

    /**
     * @BeforeScenario
     */
    public function before(ScenarioEvent $event)
    {
        if (!isset($this->silverstripe)) {
            throw new \LogicException('Context\'s $silverstripe has to be set when implementing SilverStripeAwareContextInterface.');
        }

        if (true !== require_once($this->silverstripe)) {
            $dbname = \SapphireTest::create_temp_db();
            \DB::set_alternative_database_name($dbname);
        }
    }

    /**
     * @AfterScenario
     */
    public function after(ScenarioEvent $event)
    {
        $setdb_url = $this->joinUrlParts($this->context['base_url'], '/dev/tests/setdb');
        $setdb_url = sprintf('%s?database=%s&reload=true', $setdb_url, \DB::get_alternative_database_name());
        $this->getSession()->visit($setdb_url);
    }

    /**
     * @Given /^there are the following ([^\s]*) records$/
     */
    public function thereAreTheFollowingPermissionRecords($data_object, PyStringNode $string)
    {
        if (!is_array($this->fixtures)) {
            $this->fixtures = array();
        }

        if (array_key_exists($data_object, $this->fixtures)) {
            throw new \InvalidArgumentException(sprintf('Data object `%s` already exists!', $data_object));
        }

        $fixture = array_merge(array($data_object . ':'), $string->getLines());
        $fixture = implode("\n  ", $fixture);

        // As we're dealing with split fixtures and can't join them, replace references by hand
        $fixture = preg_replace_callback('/=>(\w+)\.(\w+)/', array($this, 'replaceFixtureReferences'), $fixture);

        $this->fixtures[$data_object] = new \YamlFixture($fixture);
        $model = \DataModel::inst();
        $this->fixtures[$data_object]->saveIntoDatabase($model);
    }

    public function replaceFixtureReferences($references)
    {
        if (!array_key_exists($references[1], $this->fixtures)) {
            throw new \OutOfBoundsException(sprintf('Data object `%s` does not exist!', $references[1]));
        }
        return $this->idFromFixture($references[1], $references[2]);
    }

    protected function idFromFixture($class_name, $identifier)
    {
        return $this->fixtures[$class_name]->idFromFixture($class_name, $identifier);
    }

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

    /**
     * Joins URL parts into an URL using forward slash.
     * Forward slash usages are normalised to one between parts.
     * This method takes variable number of parameters.
     *
     * @param $...
     * @return string
     * @throws \InvalidArgumentException
     */
    public function joinUrlParts()
    {
        if (0 === func_num_args()) {
            throw new \InvalidArgumentException('Need at least one argument');
        }

        $parts = func_get_args();
        $trim_slashes = function(&$part) {
            $part = trim($part, '/');
        };
        array_walk($parts, $trim_slashes);

        return implode('/', $parts);
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
