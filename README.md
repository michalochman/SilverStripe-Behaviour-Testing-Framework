SilverStripe-Behaviour-Testing-Framework
========================================

## Getting started

As well as this package, you will need to download/install:

* [composer](http://packagist.org/)
* [PHPUnit](https://github.com/sebastianbergmann/phpunit/)
* [Selenium](http://seleniumhq.org/)

Composer dependencies file is included in this repository, so all you need to do for now on either Linux or Mac OS X is:

    # Execute the following in your project root
    # (or where composer.json is located)
    # This will install composer for you
    curl -s http://getcomposer.org/installer | php
    # This will install dependencies required
    php composer.phar install

To install PHPUnit:

    # This will install PHPUnit
    pear channel-discover pear.phpunit.de
    pear install phpunit/PHPUnit

To install Selenium:

    # This will download selenium
    wget http://selenium.googlecode.com/files/selenium-server-standalone-2.23.1.jar

## Running tests

### Starting the selenium server

You can either run the server in a separate Terminal tab:

    java -jar selenium-server-standalone-2.23.1.jar

Or you can run it in the background:

    java -jar selenium-server-standalone-2.23.1.jar > /dev/null &


### Running the tests

You will have Behat binary located in `bin` directory in your project root (or where `composer.json` is located).

By default, Behat will use Goutte driver and Selenium2 driver for `javascript` tagged scenarios.
Selenium will also try to use chrome browser. Refer to `behat.yml` for details.

    # This will run all feature tests located in `features` directory
    bin/behat

## Useful resources

* [SilverStripe CMS architecture](http://doc.silverstripe.org/sapphire/en/trunk/reference/cms-architecture)
* [SilverStripe Framework Test Module](https://github.com/silverstripe-labs/silverstripe-frameworktest)
* [SilverStripe Unit and Integration Testing](http://doc.silverstripe.org/sapphire/en/trunk/topics/testing)
