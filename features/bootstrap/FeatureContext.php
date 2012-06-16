<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\Behat\Context\Step;

// Contexts
require_once __DIR__ . '/SilverStripeContext.php';

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context.
 */
class FeatureContext extends SilverStripeContext
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

		$this->useContext('BasicContext', new BasicContext($parameters));
		$this->useContext('LoginContext', new LoginContext($parameters));
		$this->useContext('CmsPreviewContext', new CmsPreviewContext($parameters));
	}

	/**
	 * @Then /^I should see an edit page form$/
	 */
	public function stepIShouldSeeAnEditPageForm()
	{
		$page = $this->getSession()->getPage();

		$form = $page->find('css', '#Form_EditForm');
		assertNotNull($form, 'I should see an edit page form');
	}

	/**
	 * @When /^I fill in content form with "([^"]*)"$/
	 */
	public function stepIFillInContentFormWith($content)
	{
		$this->getSession()->evaluateScript("tinyMCE.get('Form_EditForm_Content').setContent('$content')");
	}

	/**
	 * @Then /^the content form should contain "([^"]*)"$/
	 */
	public function theContentFormShouldContain($content)
	{
		$this->assertElementContains('#Form_EditForm_Content', $content);
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
