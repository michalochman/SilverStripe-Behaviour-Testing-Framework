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
class BasicContext extends BehatContext
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
	 * @Then /^I should be redirected to ([^ ]+)/
	 */
	public function stepIShouldBeRedirectedTo($url)
	{
		if ($this->getMainContext()->canIntercept())
		{
			$client = $this->getSession()->getDriver()->getClient();
			$client->followRedirects(true);
			$client->followRedirect();

			$url = $this->context['base_url'] . trim($url, '"');

			assertTrue($this->getMainContext()->isCurrentUrlSimilarTo($url), sprintf('Current URL is not %s', $url));
		}
	}

	/**
	 * @Given /^I wait for "(\d+)"$/
	 */
	public function stepIWaitFor($ms)
	{
		$this->getSession()->wait($ms);
	}
}
