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

use Symfony\Component\Yaml\Yaml;

// Mink etc.
require_once 'vendor/autoload.php';

/**
 * SilverStripeContext
 *
 * Generic context wrapper used as a base for Behat FeatureContext.
 */
class SilverStripeContext extends MinkContext implements SilverStripeAwareContextInterface
{
    private $database_name;

    protected $context;
    protected $fixtures;
    protected $fixtures_lazy;
    protected $files_path;
    protected $created_files_paths;

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
    }

    public function setDatabase($database_name)
    {
        $this->database_name = $database_name;
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
     * @BeforeScenario
     */
    public function before(ScenarioEvent $event)
    {
        if (!isset($this->database_name)) {
            throw new \LogicException('Context\'s $database_name has to be set when implementing SilverStripeAwareContextInterface.');
        }

        $setdb_url = $this->joinUrlParts($this->getBaseUrl(), '/dev/tests/setdb');
        $setdb_url = sprintf('%s?database=%s', $setdb_url, $this->database_name);
        $this->getSession()->visit($setdb_url);
    }

    /**
     * @BeforeScenario @database-defaults
     */
    public function beforeDefaults(ScenarioEvent $event)
    {
        \SapphireTest::empty_temp_db();
        global $databaseConfig;
        \DB::connect($databaseConfig);
        $dataClasses = \ClassInfo::subclassesFor('DataObject');
        array_shift($dataClasses);
        foreach ($dataClasses as $dataClass) {
            \singleton($dataClass)->requireDefaultRecords();
        }
    }

    /**
     * @AfterScenario @database-defaults
     */
    public function after(ScenarioEvent $event)
    {
        \SapphireTest::empty_temp_db();
    }

    /**
     * @Given /^there are the following ([^\s]*) records$/
     */
    public function thereAreTheFollowingRecords($data_object, PyStringNode $string)
    {
        if (!is_array($this->fixtures)) {
            $this->fixtures = array();
        }
        if (!is_array($this->fixtures_lazy)) {
            $this->fixtures_lazy = array();
        }
        if (!isset($this->files_path)) {
            $this->files_path = realpath($this->getMinkParameter('files_path'));
        }
        if (!is_array($this->created_files_paths)) {
            $this->created_files_paths = array();
        }

        if (array_key_exists($data_object, $this->fixtures)) {
            throw new \InvalidArgumentException(sprintf('Data object `%s` already exists!', $data_object));
        }

        $fixture = array_merge(array($data_object . ':'), $string->getLines());
        $fixture = implode("\n  ", $fixture);

        if ('Folder' === $data_object) {
            $this->prepareTestAssetsDirectories($fixture);
        }

        if ('File' === $data_object) {
            $this->prepareTestAssetsFiles($fixture);
        }

        $fixtures_lazy = array($data_object => array());
        if (preg_match('/=>(\w+)/', $fixture)) {
            $fixture_content = Yaml::parse($fixture);
            foreach ($fixture_content[$data_object] as $identifier => &$fields) {
                foreach ($fields as $field_val) {
                    if (substr($field_val, 0, 2) == '=>') {
                        $fixtures_lazy[$data_object][$identifier] = $fixture_content[$data_object][$identifier];
                        unset($fixture_content[$data_object][$identifier]);
                    }
                }
            }
            $fixture = Yaml::dump($fixture_content);
        }

        // As we're dealing with split fixtures and can't join them, replace references by hand
//        if (preg_match('/=>(\w+)\.([\w.]+)/', $fixture, $matches)) {
//            if ($matches[1] !== $data_object) {
//                $fixture = preg_replace_callback('/=>(\w+)\.([\w.]+)/', array($this, 'replaceFixtureReferences'), $fixture);
//            }
//        }
        $fixture = preg_replace_callback('/=>(\w+)\.([\w.]+)/', array($this, 'replaceFixtureReferences'), $fixture);
        // Save fixtures into database
        $this->fixtures[$data_object] = new \YamlFixture($fixture);
        $model = \DataModel::inst();
        $this->fixtures[$data_object]->saveIntoDatabase($model);
        // Lazy load fixtures into database
        // Loop is required for nested lazy fixtures
        foreach ($fixtures_lazy[$data_object] as $identifier => $fields) {
            $fixture = array(
                $data_object => array(
                    $identifier => $fields,
                ),
            );
            $fixture = Yaml::dump($fixture);
            $fixture = preg_replace_callback('/=>(\w+)\.([\w.]+)/', array($this, 'replaceFixtureReferences'), $fixture);
            $this->fixtures_lazy[$data_object][$identifier] = new \YamlFixture($fixture);
            $this->fixtures_lazy[$data_object][$identifier]->saveIntoDatabase($model);
        }
    }

    protected function prepareTestAssetsDirectories($fixture)
    {
        $folders = Yaml::parse($fixture);
        foreach ($folders['Folder'] as $fields) {
            foreach ($fields as $field => $value) {
                if ('Filename' === $field) {
                    if (0 === strpos($value, 'assets/')) {
                        $value = substr($value, strlen('assets/'));
                    }

                    $folder_path = ASSETS_PATH . DIRECTORY_SEPARATOR . $value;
                    if (file_exists($folder_path) && !is_dir($folder_path)) {
                        throw new \Exception(sprintf('`%s` already exists and is not a directory', $this->files_path));
                    }

                    if (@mkdir($folder_path, 0777, true)) {
                        $this->created_files_paths[] = $folder_path;
                    }
                }
            }
        }
    }

    protected function prepareTestAssetsFiles($fixture)
    {
        $files = Yaml::parse($fixture);
        foreach ($files['File'] as $fields) {
            foreach ($fields as $field => $value) {
                if ('Filename' === $field) {
                    if (0 === strpos($value, 'assets/')) {
                        $value = substr($value, strlen('assets/'));
                    }

                    $file_path = $this->files_path . DIRECTORY_SEPARATOR . basename($value);
                    if (!file_exists($file_path) || !is_file($file_path)) {
                        throw new \Exception(sprintf('`%s` does not exist or is not a file', $this->files_path));
                    }
                    $asset_path = ASSETS_PATH . DIRECTORY_SEPARATOR . $value;
                    if (file_exists($asset_path) && !is_file($asset_path)) {
                        throw new \Exception(sprintf('`%s` already exists and is not a file', $this->files_path));
                    }

                    if (!file_exists($asset_path)) {
                        if (@copy($file_path, $asset_path)) {
                            $this->created_files_paths[] = $asset_path;
                        }
                    }
                }
            }
        }
    }

    protected function replaceFixtureReferences($references)
    {
        if (!array_key_exists($references[1], $this->fixtures)) {
            throw new \OutOfBoundsException(sprintf('Data object `%s` does not exist!', $references[1]));
        }
        return $this->idFromFixture($references[1], $references[2]);
    }

    protected function idFromFixture($class_name, $identifier)
    {
        if (false !== ($id = $this->fixtures[$class_name]->idFromFixture($class_name, $identifier))) {
            return $id;
        }
        if (isset($this->fixtures_lazy[$class_name], $this->fixtures_lazy[$class_name][$identifier]) &&
                false !== ($id = $this->fixtures_lazy[$class_name][$identifier]->idFromFixture($class_name, $identifier))) {
            return $id;
        }

        throw new \OutOfBoundsException(sprintf('`%s` identifier in Data object `%s` does not exist!', $identifier, $class_name));
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
     * Returns base URL parameter set in MinkExtension.
     * It simplifies configuration by allowing to specify this parameter
     * once but makes code dependent on MinkExtension.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->getMinkParameter('base_url') ?: '';
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

    /**
     * @Given /^((?:I )fill in =>(.+?) for "([^"]*)")$/
     */
    public function iFillInFor($step, $reference, $field)
    {
        if (false === strpos($reference, '.')) {
            throw new \Exception('Fixture reference should be in following format: =>ClassName.identifier');
        }

        list($class_name, $identifier) = explode('.', $reference);
        $id = $this->idFromFixture($class_name, $identifier);
        //$step = preg_replace('#=>(.+?) for "([^"]*)"#', '"'.$id.'" for "'.$field.'"', $step);

        // below is not working, because Selenium can't interact with hidden inputs
        // return new Step\Given($step);

        // TODO: investigate how to simplify this and make universal
        $javascript = <<<JAVASCRIPT
if ('undefined' !== typeof window.jQuery) {
    window.jQuery('input[name="$field"]').val($id);
}
JAVASCRIPT;
        $this->getSession()->executeScript($javascript);
    }

    /**
     * @Given /^((?:I )fill in "([^"]*)" with =>(.+))$/
     */
    public function iFillInWith($step, $field, $reference)
    {
        if (false === strpos($reference, '.')) {
            throw new \Exception('Fixture reference should be in following format: =>ClassName.identifier');
        }

        list($class_name, $identifier) = explode('.', $reference);
        $id = $this->idFromFixture($class_name, $identifier);
        //$step = preg_replace('#"([^"]*)" with =>(.+)#', '"'.$field.'" with "'.$id.'"', $step);

        // below is not working, because Selenium can't interact with hidden inputs
        // return new Step\Given($step);

        // TODO: investigate how to simplify this and make universal
        $javascript = <<<JAVASCRIPT
if ('undefined' !== typeof window.jQuery) {
    window.jQuery('input[name="$field"]').val($id);
}
JAVASCRIPT;
        $this->getSession()->executeScript($javascript);
    }
}
