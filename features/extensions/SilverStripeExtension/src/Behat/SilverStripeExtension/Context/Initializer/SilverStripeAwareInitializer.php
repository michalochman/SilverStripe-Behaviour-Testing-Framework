<?php

namespace Behat\SilverStripeExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface,
Behat\Behat\Context\ContextInterface;

use Behat\SilverStripeExtension\Context\SilverStripeAwareContextInterface;

/*
 * This file is part of the Behat/SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SilverStripe aware contexts initializer.
 * Sets SilverStripe instance to the SilverStripeAware contexts.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
class SilverStripeAwareInitializer implements InitializerInterface
{
    private $database_name;
    private $ajax_steps;

    /**
     * Initializes initializer.
     */
    public function __construct($framework_path, $framework_host, $ajax_steps)
    {
        $this->bootstrap($framework_path, $framework_host);
        $this->database_name = $this->initializeTempDb();
        $this->ajax_steps = $ajax_steps;
    }

    public function __destruct()
    {
        $this->deleteTempDb();
    }

    /**
     * Checks if initializer supports provided context.
     *
     * @param ContextInterface $context
     *
     * @return Boolean
     */
    public function supports(ContextInterface $context)
    {
        return $context instanceof SilverStripeAwareContextInterface;
    }

    /**
     * Initializes provided context.
     *
     * @param ContextInterface $context
     */
    public function initialize(ContextInterface $context)
    {
        $context->setDatabase($this->database_name);
        $context->setAjaxEnabledSteps($this->ajax_steps);
    }

    protected function bootstrap($framework_path, $framework_host)
    {
        file_put_contents('php://stderr', 'Bootstrapping' . PHP_EOL);

        // Set file to URL mappings
        global $_FILE_TO_URL_MAPPING;
        $_FILE_TO_URL_MAPPING[dirname($framework_path)] = $framework_host;

        // Connect to database
        require_once $framework_path . '/core/Core.php';

        // Remove the error handler so that PHPUnit can add its own
        restore_error_handler();
    }

    protected function initializeTempDb()
    {
        file_put_contents('php://stderr', 'Creating temp DB' . PHP_EOL);
        $dbname = \SapphireTest::create_temp_db();
        \DB::set_alternative_database_name($dbname);

        return $dbname;
    }

    protected function deleteTempDb()
    {
        file_put_contents('php://stderr', 'Killing temp DB' . PHP_EOL);
        \SapphireTest::kill_temp_db();
        \DB::set_alternative_database_name(null);
    }
}