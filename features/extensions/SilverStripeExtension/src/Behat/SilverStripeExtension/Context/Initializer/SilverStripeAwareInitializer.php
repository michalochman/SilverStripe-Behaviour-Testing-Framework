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
    private $session_key;
    private $session_file;

    /**
     * Initializes initializer.
     */
    public function __construct($framework_path, $framework_host)
    {
        $this->bootstrap($framework_path, $framework_host);
        $database_config = $this->initialize_temp_db();

        $this->session_key = $this->generate_session_key($database_config);
        $this->session_file = $this->persist_session($database_config, $this->session_key);
    }

    public function __destruct()
    {
        $this->delete_temp_db();
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
        $context->setSessionKey($this->session_key);
    }

    protected function bootstrap($framework_path, $framework_host)
    {
        file_put_contents('php://stderr', 'Bootstrapping'.PHP_EOL);

        // Set file to URL mappings
        global $_FILE_TO_URL_MAPPING;
        $_FILE_TO_URL_MAPPING[dirname($framework_path)] = $framework_host;

        // Connect to database
        require_once $framework_path . '/core/Core.php';

        // Remove the error handler so that PHPUnit can add its own
        restore_error_handler();
    }

    protected function initialize_temp_db()
    {
        file_put_contents('php://stderr', 'Creating temp DB'.PHP_EOL);
        $dbname = \SapphireTest::create_temp_db();
        \DB::set_alternative_database_name($dbname);
        $database_config = array(
            'databaseConfig' => array(
                'database' => $dbname,
            ),
        );

        $this->populate_temp_db();

        return json_encode($database_config);
    }

    protected function populate_temp_db()
    {
        file_put_contents('php://stderr', 'Populating temp DB with defaults.'.PHP_EOL);
        global $databaseConfig;
        \DB::connect($databaseConfig);
        $dataClasses = \ClassInfo::subclassesFor('DataObject');
        array_shift($dataClasses);
        foreach($dataClasses as $dataClass) {
            \singleton($dataClass)->requireDefaultRecords();
        }
    }

    protected function delete_temp_db()
    {
        file_put_contents('php://stderr', 'Killing temp DB'.PHP_EOL);
        \SapphireTest::kill_temp_db();
        \DB::set_alternative_database_name(null);
    }

    protected function generate_session_key($database_config)
    {
        return sha1(sprintf('%s%s', $database_config, microtime(true)));
    }

    protected function persist_session($database_config, $session_key)
    {
        file_put_contents('php://stderr', 'Saving testSessionKey file'.PHP_EOL);
        $temp_dir = '/tmp';
        $test_sessions_dir = $temp_dir . DIRECTORY_SEPARATOR . 'testsessions';
        if (!file_exists($test_sessions_dir)) {
            mkdir($test_sessions_dir);
        }
        $test_session_file = $test_sessions_dir . DIRECTORY_SEPARATOR . $session_key;
        file_put_contents($test_session_file, $database_config);

        return $test_session_file;
    }

    protected function forget_session()
    {
        file_put_contents('php://stderr', 'Removing testSessionKey file'.PHP_EOL);
        if (file_exists($this->session_file)) {
            unlink($this->session_file);
        }
    }
}