<?php

namespace SilverStripe\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Exception\PendingException;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

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
     * @AfterStep ~@modal
     *
     * Excluding scenarios with @modal tag is required,
     * because modal dialogs stop any JS interaction
     */
    public function appendErrorHandlerBeforeStep(StepEvent $event)
    {
        $javascript = <<<JS
window.onerror = function(msg) {
    var body = document.getElementsByTagName('body')[0];
    body.setAttribute('data-jserrors', '[captured JavaScript error] ' + msg);
}
if ('undefined' !== typeof window.jQuery) {
    window.jQuery('body').ajaxError(function(event, jqxhr, settings, exception) {
        window.onerror(event.type + ': ' + settings.type + ' ' + settings.url + ' ' + exception);
    });
}
JS;

        $this->getSession()->executeScript($javascript);
    }

    /**
     * @AfterStep ~@modal
     *
     * Excluding scenarios with @modal tag is required,
     * because modal dialogs stop any JS interaction
     */
    public function readErrorHandlerAfterStep(StepEvent $event)
    {
        $page = $this->getSession()->getPage();

        $jserrors = $page->find('xpath', '//body[@data-jserrors]');
        if (null !== $jserrors) {
            throw new \Exception($jserrors->getAttribute('data-jserrors'));
        }
    }

    /**
     * Hook into jQuery ajaxStart, ajaxSuccess and ajaxComplete events.
     * Prepare __ajaxStatus() functions and attach them to these handlers.
     * Event handlers are removed after one run.
     *
     * @BeforeStep
     */
    public function handleAjaxBeforeStep(StepEvent $event)
    {
        if (!preg_match('/(go to|follow|press|click|submit)/i', $event->getStep()->getText())) {
            return;
        }

        $javascript = <<<JS
if ('undefined' !== typeof window.jQuery) {
    window.jQuery(document).on('ajaxStart.ss.test.behaviour', function(){
        window.__ajaxStatus = function() {
            return 'waiting';
        };
    });
    window.jQuery(document).on('ajaxComplete.ss.test.behaviour', function(e, jqXHR){
        if (null === jqXHR.getResponseHeader('X-ControllerURL')) {
            window.__ajaxStatus = function() {
                return 'no ajax';
            };
        }
    });
    window.jQuery(document).on('ajaxSuccess.ss.test.behaviour', function(e, jqXHR){
        if (null === jqXHR.getResponseHeader('X-ControllerURL')) {
            window.__ajaxStatus = function() {
                return 'success';
            };
        }
    });
}
JS;
        $this->getSession()->executeScript($javascript);
    }

    /**
     * Wait for the __ajaxStatus()to return anything but 'waiting'.
     * Don't wait longer than 5 seconds.
     *
     * Don't unregister handler if we're dealing with modal windows
     *
     * Below are the strings that are matched against step name
     * that is possibly using AJAX
     * @match     go to
     * @match     follow
     * @match     press
     * @match     click
     * @match     submit
     *
     * @AfterStep ~@modal
     */
    public function handleAjaxAfterStep(StepEvent $event)
    {
        $method = new \ReflectionMethod(__CLASS__, substr(__METHOD__, strpos(__METHOD__, '::') + 2));
        if (preg_match_all('#\* @match (.+)#', $method->getDocComment(), $m)) {
            array_walk($m[1], create_function('&$val', '$val = trim($val);'));
            $matches = implode('|', array_filter($m[1]));

            if (!preg_match('/(?:' . $matches . ')/i', $event->getStep()->getText())) {
                return;
            }

            $this->handleAjaxTimeout();

            $javascript = <<<JS
if ('undefined' !== typeof window.jQuery) {
    window.jQuery(document).off('ajaxStart.ss.test.behaviour');
    window.jQuery(document).off('ajaxComplete.ss.test.behaviour');
    window.jQuery(document).off('ajaxSuccess.ss.test.behaviour');
}
JS;
            $this->getSession()->executeScript($javascript);
        }
    }

    public function handleAjaxTimeout()
    {
        $this->getSession()->wait(5000,
            "(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
        );

        // wait additional 100ms to allow DOM to update
        $this->getSession()->wait(100);
    }

    /**
     * Take screenshot when step fails.
     * Works only with Selenium2Driver.
     *
     * @AfterStep
     */
    public function takeScreenshotAfterFailedStep(StepEvent $event)
    {
        if (4 === $event->getResult()) {
            $driver = $this->getSession()->getDriver();
            // quit silently when unsupported
            if (!($driver instanceof Selenium2Driver)) {
                return;
            }

            $parent = $event->getLogicalParent();
            $feature = $parent->getFeature();
            $step = $event->getStep();

            if (isset($this->context['screenshot_path'])) {
                $screenshot_path = realpath($this->context['screenshot_path']);
            } else {
                $screenshot_path = realpath(sys_get_temp_dir());
            }

            if (!is_dir($screenshot_path)) {
                file_put_contents('php://stderr', sprintf('"%s" is not valid directory' . PHP_EOL, $this->context['screenshot_path']));
                return;
            }
            if (!is_writable($screenshot_path)) {
                file_put_contents('php://stderr', sprintf('"%s" directory is not writable' . PHP_EOL, $screenshot_path));
                return;
            }

            $screenshot_path = sprintf('%s/%s_%d.png', $screenshot_path, basename($feature->getFile()), $step->getLine());
            $screenshot = $driver->wdSession->screenshot();
            file_put_contents($screenshot_path, base64_decode($screenshot));
            file_put_contents('php://stderr', sprintf('Saving screenshot into %s' . PHP_EOL, $screenshot_path));
        }
    }

    /**
     * @Then /^I should be redirected to "([^"]+)"/
     */
    public function stepIShouldBeRedirectedTo($url)
    {
        if ($this->getMainContext()->canIntercept()) {
            $client = $this->getSession()->getDriver()->getClient();
            $client->followRedirects(true);
            $client->followRedirect();

            $url = $this->getMainContext()->joinUrlParts($this->context['base_url'], $url);

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

    /**
     * @Given /^I click "([^"]*)" in the "([^"]*)" element$/
     */
    public function iClickInTheElement($text, $selector)
    {
        $page = $this->getSession()->getPage();

        $parent_element = $page->find('css', $selector);
        assertNotNull($parent_element, sprintf('"%s" element not found', $selector));

        $element = $parent_element->find('xpath', sprintf('//*[count(*)=0 and contains(.,"%s")]', $text));
        assertNotNull($element, sprintf('"%s" not found', $text));

        $element->click();
    }

    /**
     * @Given /^I type "([^"]*)" into the dialog$/
     */
    public function iTypeIntoTheDialog($data)
    {
        $data = array(
            'text' => $data,
        );
        $this->getSession()->getDriver()->wdSession->postAlert_text($data);
    }

    /**
     * @Given /^I confirm the dialog$/
     */
    public function iConfirmTheDialog()
    {
        $this->getSession()->getDriver()->wdSession->accept_alert();
        $this->handleAjaxTimeout();
    }

    /**
     * @Given /^I dismiss the dialog$/
     */
    public function iDismissTheDialog()
    {
        $this->getSession()->getDriver()->wdSession->dismiss_alert();
        $this->handleAjaxTimeout();
    }

    /**
     * @Given /^(I attach the file .*) with HTML5$/
     */
    public function iAttachTheFileTo($step)
    {
        $this->getSession()->evaluateScript("jQuery('.ss-uploadfield-editandorganize').show()");
        $this->getSession()->evaluateScript("jQuery('[name=\"AssetUploadField\"]').css({opacity:1,visibility:'visible',height:'1px',width:'1px'})");
        $this->getSession()->evaluateScript("jQuery('[name=\"files[]\"]').css({opacity:1,visibility:'visible',height:'1px',width:'1px'})");
        $this->getSession()->wait(1000);

        return new Step\Given($step);
    }
}
