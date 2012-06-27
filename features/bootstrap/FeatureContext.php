<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\SilverStripeExtension\Context\SilverStripeAwareContextInterface;

use SilverStripe\Test\Behaviour\SilverStripeContext,
    SilverStripe\Test\Behaviour\BasicContext,
    SilverStripe\Test\Behaviour\LoginContext,
    SilverStripe\Test\Behaviour\CmsFormsContext,
    SilverStripe\Test\Behaviour\CmsUiContext;

// Contexts
require_once __DIR__ . '/SilverStripe/Test/Behaviour/SilverStripeContext.php';

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context
 *
 * Context automatically loaded by Behat.
 * Uses subcontexts to extend functionality.
 */
class FeatureContext extends SilverStripeContext implements SilverStripeAwareContextInterface
{
    protected $context;
    protected $silverstripe;

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
        $this->useContext('CmsUiContext', new CmsUiContext($parameters));
    }

    public function setSilverstripe($silverstripe)
    {
        $this->silverstripe = $silverstripe;
    }

    /**
     * @BeforeScenario
     */
    public function before(ScenarioEvent $event)
    {
        if (!isset($event->getContext()->silverstripe)) {
            throw new LogicException('Context\'s $silverstripe has to be set when implementing SilverStripeAwareContextInterface.');
        }

        require_once($event->getContext()->silverstripe);
        $dbname = SapphireTest::create_temp_db();
        DB::set_alternative_database_name($dbname);
    }

    /**
     * @AfterScenario
     */
    public function after(ScenarioEvent $event)
    {
        SapphireTest::kill_temp_db();
        DB::set_alternative_database_name(null);
    }
}
