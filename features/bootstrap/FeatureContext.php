<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

// Third party libraries
require_once 'mink/autoload.php';
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

class FeatureContext extends Behat\Mink\Behat\Context\MinkContext
{

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $kernel)
        $this->useContext('mink_redirect', new \Behat\CommonContexts\MinkRedirectContext($kernel));
    }
}
