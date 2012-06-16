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
		$this->useContext('CmsFormsContext', new CmsFormsContext($parameters));
		$this->useContext('CmsPanelContext', new CmsPanelContext($parameters));
		$this->useContext('CmsPreviewContext', new CmsPreviewContext($parameters));
		$this->useContext('CmsTreeContext', new CmsTreeContext($parameters));
	}

}
