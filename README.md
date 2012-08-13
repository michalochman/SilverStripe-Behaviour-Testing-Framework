SilverStripe-Behaviour-Testing-Framework
========================================

## Getting started

As well as this package, you will need to download/install:

* [composer](http://packagist.org/)
* [PHPUnit](https://github.com/sebastianbergmann/phpunit/)
* [Selenium](http://seleniumhq.org/)

Composer dependencies file is included in this repository, so all you need to do for now
on either Linux or Mac OS X is:

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
    wget http://selenium.googlecode.com/files/selenium-server-standalone-2.25.0.jar

## Configuration

`admin_url` and `login_url` should not be changed unless you customized them somehow.

Optional `screenshot_path` variable is used to store screenshot of a last know state
of a failed step. It defaults to whatever is returned by PHP's `sys_get_temp_dir()`.
Screenshot names within that directory consist of feature file filename and line
number that failed.

    # behat.yml
    default:
      # ...
      context:
        parameters:
          admin_url: /admin/
          login_url: /Security/login
          screenshot_path: features/screenshots/

### Configuring extensions

#### MinkExtension

You will probably need to change the base URL that is used during the test process.
It is used every time you use relative URLs in your feature descriptions.
It will also be used by [file to URL mapping](http://doc.silverstripe.org/framework/en/topics/commandline#configuration) in `SilverStripeExtension`.

You also have to change `files_path` path when you want to support file uploads.
Otherwise, you can remove it from the config. Currently only absolute paths are supported.

Only selenium2 sessions are supported at the moment, but `default_session` is the place
to change it if you want to try other driver sessions like `goutte`.

    # behat.yml
    default:
      # ...
      extensions:
        Behat\MinkExtension\Extension:
            base_url:  http://localhost
            files_path: /absolute/path/to/files/
            default_session: selenium2
            selenium2: ~

#### SilverStripeExtension

You also can change the path to the SilverStripe framework with `framework_path`.
It supports both absolute and relative (to `behat.yml` file) paths.

Because SilverStripe uses AJAX requests quite extensively, we had to invent a way
to deal with them more efficiently and less verbose than just
Optional `ajax_steps` is used to match steps defined there so they can be "caught" by
[special AJAX handlers](http://blog.scur.pl/2012/06/ajax-callback-support-behat-mink/) that tweak the delays.
You can either use a pipe delimited string or a list of substrings that match step definition.

    # behat.yml
    default:
      # ...
      extensions:
        features/extensions/SilverStripeExtension/init.php:
          framework_path: ../../
          # ajax_steps: "go to|follow|press|click|submit"
          ajax_steps:
            - go to
            - follow
            - press
            - click
            - submit

### Additional profiles

By default, `MinkExtension` is using `FirefoxDriver`.
Let's say you want to user `ChromeDriver` too.

You can either override the `selenium2` setting in default profile or add another
profile that can be run using `bin/behat --profile=PROFILE_NAME`, where `PROFILE_NAME`
could be `chrome`.

    chrome:
      extensions:
          Behat\MinkExtension\Extension:
            selenium2:
              capabilities:
                browserName: chrome
                version: ANY

## Running tests

### Starting the selenium server

You can either run the server in a separate Terminal tab:

    java -jar selenium-server-standalone-2.25.0.jar

Or you can run it in the background:

    java -jar selenium-server-standalone-2.25.0.jar > /dev/null &


### Running the tests

You will have Behat binary located in `bin` directory in your project root (or where `composer.json` is located).

By default, Behat will use Goutte driver and Selenium2 driver for `javascript` tagged scenarios.
Selenium will also try to use chrome browser. Refer to `behat.yml` for details.

    # This will run all feature tests located in `features` directory
    bin/behat

    # This will run all feature tests using chrome profile
    bin/behat --profile=chrome

## Useful resources

* [SilverStripe CMS architecture](http://doc.silverstripe.org/sapphire/en/trunk/reference/cms-architecture)
* [SilverStripe Framework Test Module](https://github.com/silverstripe-labs/silverstripe-frameworktest)
* [SilverStripe Unit and Integration Testing](http://doc.silverstripe.org/sapphire/en/trunk/topics/testing)
