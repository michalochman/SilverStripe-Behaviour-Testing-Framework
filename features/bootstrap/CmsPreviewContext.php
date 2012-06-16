<?php

use Behat\Behat\Context\ClosuredContextInterface,
Behat\Behat\Context\TranslatedContextInterface,
Behat\Behat\Context\BehatContext,
Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
Behat\Gherkin\Node\TableNode;

use Behat\Behat\Context\Step;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context.
 */
class CmsPreviewContext extends BehatContext
{
	protected $context;

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

	/**
	 * Get Mink session from MinkContext
	 */
	public function getSession($name = null)
	{
		return $this->getMainContext()->getSession($name);
	}

	/**
	 * @Then /^I can see the preview panel$/
	 */
	public function iCanSeeThePreviewPanel()
	{
		$this->getMainContext()->assertElementOnPage('.cms-preview');
	}

	/**
	 * @Given /^the preview contains "([^"]*)"$/
	 */
	public function thePreviewContains($content)
	{
		$driver = $this->getSession()->getDriver();
		$driver->switchToIFrame('cms-preview-iframe');

		$this->getMainContext()->assertPageContainsText($content);
	}
}
