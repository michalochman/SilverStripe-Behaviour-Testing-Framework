<?php

namespace SilverStripe\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step,
    Behat\Behat\Exception\PendingException;
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
     * @BeforeStep @javascript
     */
    public function beforeStep($event)
    {
        if (preg_match('/(go to|follow|press|click|submit)/i', $event->getStep()->getText())) {
            $this->ajaxClickHandler_before();
        }
    }

    /**
     * @AfterStep @javascript
     */
    public function afterStep($event)
    {
        if (preg_match('/(go to|follow|press|click|submit)/i', $event->getStep()->getText())) {
            $this->ajaxClickHandler_after();
        }
    }

    /**
     * Hook into jQuery ajaxStart, ajaxSuccess and ajaxComplete events.
     * Prepare __ajaxStatus() functions and attach them to these handlers.
     * Event handlers are removed after one run.
     */
    public function ajaxClickHandler_before()
    {
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
     */
    public function ajaxClickHandler_after()
    {
        $this->getSession()->wait(5000,
            "(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
        );
        $javascript = <<<JS
if ('undefined' !== typeof window.jQuery) {
    window.jQuery(document).off('ajaxStart.ss.test.behaviour');
    window.jQuery(document).off('ajaxComplete.ss.test.behaviour');
    window.jQuery(document).off('ajaxSuccess.ss.test.behaviour');
}
JS;
        $this->getSession()->executeScript($javascript);
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

        // for not yet known reason, the below 'content' named selector is not working
        /*$parent_element = $page->find('css', $selector);
        assertNotNull($parent_element, sprintf('"%s" element not found', $selector));

        $element = $parent_element->find('named', array('content', "'$text'"));
        assertNotNull($element, sprintf('"%s" not found', $text));

        $element->click();*/
        // triggering click by JS because of above
        $javascript = <<<JS
if ('undefined' !== typeof window.jQuery) {
    return window.jQuery('$selector :contains("$text")').trigger('click');
}
JS;
        $this->getSession()->executeScript($javascript);
    }

    /**
     * @Given /^I type "([^"]*)" into the dialog$/
     */
    public function iTypeIntoTheDialog($data)
    {
        $this->getSession()->getDriver()->wdSession->postAlert_Text($data);
    }

    /**
     * @Given /^I confirm the dialog$/
     */
    public function iConfirmTheDialog()
    {
        $this->getSession()->getDriver()->wdSession->accept_alert();
    }

    /**
     * @Given /^I dismiss the dialog$/
     */
    public function iDismissTheDialog()
    {
        $this->getSession()->getDriver()->wdSession->dismiss_alert();
    }
}
