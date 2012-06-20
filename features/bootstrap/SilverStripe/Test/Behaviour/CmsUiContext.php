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
 * CmsUiContext
 *
 * Context used to define steps related to SilverStripe CMS UI like Tree or Panel.
 */
class CmsUiContext extends BehatContext
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
		$this->getSession()->wait(5000, "window.jQuery('.cms-tree').size() > 0");

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

	/**
	 * @When /^I expand Filter CMS Panel$/
	 */
	public function iExpandFilterCmsPanel()
	{
		$page = $this->getSession()->getPage();

		$panel_toggle_element = $page->find('css', '.cms-content > .cms-panel > .cms-panel-toggle > .toggle-expand');
		assertNotNull($panel_toggle_element, 'Panel toggle not found');

		if ($panel_toggle_element->isVisible())
		{
			$panel_toggle_element->click();
		}
	}

}
