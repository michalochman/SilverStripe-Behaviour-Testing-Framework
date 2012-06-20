<?php

namespace SilverStripe\Test\Behaviour;

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
 * BasicContext
 *
 * Context used to define generic steps like following anchors or pressing buttons.
 * Handles timeouts.
 * Handles redirections.
 * Handles AJAX enabled links, buttons and forms - jQuery is assumed.
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
	 * @BeforeStep @javascript
	 */
	public function beforeStep($event)
	{
		if (preg_match('/(go to|follow|press|click|submit)/i', $event->getStep()->getText()))
		{
			$this->ajaxClickHandler_before();
		}
	}

	/**
	 * @AfterStep @javascript
	 */
	public function afterStep($event)
	{
		if (preg_match('/(go to|follow|press|click|submit)/i', $event->getStep()->getText()))
		{
			$this->ajaxClickHandler_after();
		}
	}

	/**
	 * Hook into jQuery ajaxStart, ajaxSuccess and ajaxComplete events.
	 * Prepare __ajaxStatus() functions and attach them to these handlers.
	 * Event handlers are removed after one run.
	 */
	public function ajaxClickHandler_before() {
		$javascript = <<<JS
window.jQuery(document).one('ajaxStart.ss.test.behaviour', function(){
	window.__ajaxStatus = function() {
		return 'waiting';
	};
});
window.jQuery(document).one('ajaxComplete.ss.test.behaviour', function(e, jqXHR){
	if (null === jqXHR.getResponseHeader('X-ControllerURL')) {
		window.__ajaxStatus = function() {
			return 'no ajax';
		};
	}
});
window.jQuery(document).one('ajaxSuccess.ss.test.behaviour', function(e, jqXHR){
	if (null === jqXHR.getResponseHeader('X-ControllerURL')) {
		window.__ajaxStatus = function() {
			return 'success';
		};
	}
});
JS;
		$this->getSession()->executeScript($javascript);
	}

	/**
	 * Wait for the __ajaxStatus()to return anything but 'waiting'.
	 * Don't wait longer than 5 seconds.
	 */
	public function ajaxClickHandler_after() {
		$this->getSession()->wait(5000,
			"(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
		);
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

	/**
	 * @Given /^I press "([^"]*)" button$/
	 */
	public function stepIPressButton($button)
	{
		$page = $this->getSession()->getPage();

		$button_element = $page->find('named', array('link_or_button', "'$button'"));
		assertNotNull($button_element, sprintf('%s button not found', $button));

		$button_element->click();
	}
}
