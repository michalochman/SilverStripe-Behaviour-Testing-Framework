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
	 * @BeforeStep
	 */
	public function beforeStep($event)
	{
		if (preg_match('/(press|click|submit)/i', $event->getStep()->getText()))
		{
			$this->ajaxClickHandler_before();
		}
	}

	/**
	 * @AfterStep
	 */
	public function afterStep($event)
	{
		if (preg_match('/(press|click|submit)/i', $event->getStep()->getText()))
		{
			$this->ajaxClickHandler_after();
		}
	}

	public function ajaxClickHandler_before() {
		$javascript = <<<JS
window.jQuery(document).on('ajaxStart', function(){
	window.__ajaxStatus = function() {
		return 'waiting';
	};
});
window.jQuery(document).on('ajaxComplete', function(){
	window.__ajaxStatus = function() {
		return 'no ajax';
	};
});
window.jQuery(document).on('ajaxSuccess', function(){
	window.__ajaxStatus = function() {
		return 'success';
	};
});
JS;
		$this->getSession()->executeScript($javascript);
	}

	public function ajaxClickHandler_after() {
		$this->getSession()->wait(5000,
			"(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
		);
		$javascript = <<<JS
window.jQuery(document).off('ajaxStart');
window.jQuery(document).off('ajaxComplete');
window.jQuery(document).off('ajaxSuccess');
JS;
		$this->getSession()->executeScript($javascript);
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
	 * @Then /^I should see the CMS$/
	 */
	public function iShouldSeeTheCms()
	{
		$page = $this->getSession()->getPage();
		$cms_element = $page->find('css', '.cms');
		assertNotNull($cms_element, 'CMS not found');
	}

	/**
	 * @Then /^I should see "([^"]*)" notice$/
	 */
	public function iShouldSeeNotice($notice)
	{
		$this->getMainContext()->assertElementContains('.notice-wrap', $notice);
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
