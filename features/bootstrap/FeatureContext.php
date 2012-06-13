<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\Step;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Driver\Selenium2Driver;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

// Mink etc.
require_once 'vendor/autoload.php';

/**
 * Features context.
 */
class FeatureContext extends MinkContext
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

	public function canIntercept()
	{
		$driver = $this->getSession()->getDriver();
		if ($driver instanceof GoutteDriver)
		{
			return true;
		}
		else if ($driver instanceof Selenium2Driver)
		{
			return false;
		}

		throw new UnsupportedDriverActionException('You need to tag the scenario with "@mink:goutte" or "@mink:symfony". Intercepting the redirections is not supported by %s', $driver);
	}
	
	/**
	 * @Given /^(.*) without redirection$/
	 */
	public function theRedirectionsAreIntercepted($step)
	{
		if ($this->canIntercept())
		{
			$this->getSession()->getDriver()->getClient()->followRedirects(false);
		}

		return new Step\Given($step);
	}

	/**
	 * @Then /^I should be redirected to ([^ ]+)/
	 */
	public function stepIShouldBeRedirectedTo($url)
	{
		if ($this->canIntercept())
		{
			$client = $this->getSession()->getDriver()->getClient();
			$client->followRedirects(true);
			$client->followRedirect();

			$url = $this->context['base_url'] . trim($url, '"');

			assertStringStartsWith($url, $this->getSession()->getCurrentUrl(), sprintf('Current URL is not %s', $url));
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
	 * @Given /^I am logged in$/
	 */
	public function stepIAmLoggedIn()
	{
		$this->getSession()->visit($this->context['base_url'] . $this->context['admin_url']);

		if (0 == strpos($this->getSession()->getCurrentUrl(), $this->context['base_url'] . $this->context['login_url']))
		{
			$this->stepILogInWith('admin', 'password');
			assertStringStartsWith($this->context['base_url'] . $this->context['admin_url'], $this->getSession()->getCurrentUrl());
		}
	}

	/**
	 * @Given /^I am not logged in$/
	 */
	public function stepIAmNotLoggedIn()
	{
		$this->getSession()->reset();
	}

	/**
	 * @Given /^I should see a log-in form$/
	 */
  public function stepIShouldSeeALogInForm()
  {
    $page = $this->getSession()->getPage();

		$login_form = $page->find('css', '#MemberLoginForm_LoginForm');
	  assertNotNull($login_form, 'I should see a log-in form');
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
	 * @When /^I log in with "([^"]*)" and "([^"]*)"$/
	 */
	public function stepILogInWith($email, $password)
	{
		$this->getSession()->visit($this->context['base_url'] . $this->context['login_url']);
		
		$page = $this->getSession()->getPage();

		$email_field = $page->find('css', '[name=Email]');
		$password_field = $page->find('css', '[name=Password]');
		$submit_button = $page->find('css', '[type=submit]');
		$email_field->setValue($email);
		$password_field->setValue($password);
		$submit_button->press();
	}

	/**
	 * @Then /^I will see a bad log-in message$/
	 */
	public function stepIWillSeeABadLogInMessage()
	{
		$page = $this->getSession()->getPage();

		$bad_message = $page->find('css', '.message.bad');
		
		assertNotNull($bad_message, 'Bad message not found.');
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
	 * @Then /^I should see "([^"]*)" notice$/
	 */
	public function iShouldSeeNotice($notice)
	{
		$this->assertElementContains('.notice-wrap', $notice);
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

	/**
	 * @Then /^I can see the preview panel$/
	 */
	public function iCanSeeThePreviewPanel()
	{
		$this->assertElementOnPage('.cms-preview');
	}

	/**
	 * @Given /^the preview contains "([^"]*)"$/
	 */
	public function thePreviewContains($content)
	{
		$driver = $this->getSession()->getDriver();
		$driver->switchToIFrame('cms-preview-iframe');

		$this->assertPageContainsText($content);
	}
}
