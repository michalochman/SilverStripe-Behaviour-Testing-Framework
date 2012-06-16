<?php

use Behat\Behat\Context\ClosuredContextInterface,
Behat\Behat\Context\TranslatedContextInterface,
Behat\Behat\Context\BehatContext,
Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\Step;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context.
 */
class LoginContext extends BehatContext
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
	 * @Given /^I should see a log-in form$/
	 */
	public function stepIShouldSeeALogInForm()
	{
		$page = $this->getSession()->getPage();

		$login_form = $page->find('css', '#MemberLoginForm_LoginForm');
		assertNotNull($login_form, 'I should see a log-in form');
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
}
