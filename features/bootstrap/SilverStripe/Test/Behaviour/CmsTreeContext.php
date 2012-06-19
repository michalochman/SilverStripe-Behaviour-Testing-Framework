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
 * Features context.
 */
class CmsTreeContext extends BehatContext
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

	protected function getCmsTreeElement()
	{
		$page = $this->getSession()->getPage();
		$cms_tree_element = $page->find('css', '.cms-tree');
		assertNotNull($cms_tree_element, 'CMS tree not found');

		return $cms_tree_element;
	}

	/**
	 * @When /^I should see "([^"]*)" in CMS Tree$/
	 */
	public function stepIShouldSeeInCmsTree($text)
	{
		$cms_tree_element = $this->getCmsTreeElement();

		$element = $cms_tree_element->find('named', array('content', "'$text'"));
		assertNotNull($element, sprintf('%s not found', $text));
	}

	/**
	 * @When /^I should not see "([^"]*)" in CMS Tree$/
	 */
	public function stepIShouldNotSeeInCmsTree($text)
	{
		$cms_tree_element = $this->getCmsTreeElement();

		$element = $cms_tree_element->find('named', array('content', "'$text'"));
		assertNull($element, sprintf('%s found', $text));
	}

}
