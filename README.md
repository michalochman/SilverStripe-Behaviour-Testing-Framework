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


### Additional requirements

Because this testing framework is intended to be used with SilverStripe, you have to
download and install it too. The minimum version required is `3.0`. Recommended way
to get started is to [install from source](http://doc.silverstripe.org/framework/en/installation/from-source),
but it's really up to your preference.

No database content is required because it will be created automatically when needed.

## Configuration

`admin_url` and `login_url` should not be changed unless you customized them somehow.

Optional `screenshot_path` variable is used to store screenshot of a last known state
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
        Behat\SilverStripeExtension\Extension:
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

By default, Behat will use Selenium2 driver.
Selenium will also try to use chrome browser. Refer to `behat.yml` for details.

    # This will run all feature tests located in `features` directory
    bin/behat

    # This will run all feature tests using chrome profile
    bin/behat --profile=chrome

## FAQ

### Why does the module need to know about the framework path on the filesystem?

Sometimes SilverStripe needs to know the URL of your site. When you're visiting
your site in a web browser this is easy to work out, but if you're executing
scripts on the command-line, it has no way of knowing.

To work this out, this module is using [file to URL mapping](http://doc.silverstripe.org/framework/en/topics/commandline#configuration).

### How does the module interact with the SS database?

The module creates temporary database on init and is switching to the alternative
database session before every scenario by using `/dev/tests/setdb` TestRunner
endpoint.

It also populates this temporary database with the default records if necessary.

It is possible to include your own fixtures, it is explained further.

### How do I define fixtures?

Fixtures should be provided in YAML format (standard SilverStripe fixture format)
as [PyStrings](http://docs.behat.org/guides/1.gherkin.html#pystrings)

Take a look at the sample fixture logic first:

    Given there are the following Permission records
      """
      admin:
        Code: ADMIN
      """
    And there are the following Group records
      """
      admingroup:
        Title: Admin Group
        Code: admin
        Permissions: =>Permission.admin
      """
    And there are the following Member records
      """
      admin:
        FirstName: Admin
        Email: admin@test.com
        Groups: =>Group.admingroup
      """

In this example, the fixture is used to create Admin member with admin permissions.

As you can see, there are special Gherkin steps that take care of loading
fixtures into database. They use the following format:

    Given there are the following TableName records
      """
      RowIdentifier:
        ColumnName: Value
      """

Fixtures may also use a `=>` symbol to indicate relationships between records.
In the example above `=>Permission.admin` will be replaced with row `ID` of a
`Permission` record that has `RowIdentifier` set as `admin`.

### When do fixtures get created?

Fixtures are created where you defined them. If you want the fixtures to be created
before every scenario, define them in [Background](http://docs.behat.org/guides/1.gherkin.html#backgrounds).

If you want them to be created only when a particular scenario runs, define them there.

### When do fixtures get cleared during the feature runs?

Fixtures are usually not cleared between scenarios. You can alter this behaviour
by tagging the feature or scenario with `@database-defaults` tag.

The module runner empties the database before each scenario tagged with
`@database-defaults` and populates it with default records (usually a set of
default pages).

### How do I debug when something goes wrong?

First, read the console output. Behat will tell you which steps have failed.

SilverStripe Behaviour Testing Framework also notifies you about some events.
It tries to catch some JavaScript errors and AJAX errors as well although it
is limited to errors that occur after the page is loaded.

Screenshot will be taken by the module every time the step is marked as failed.
Refer to configuration section above to know how to set up the screenshot path.

If you are unable to debug using the information collected with the above
methods, it is possible to delay the step execution by adding the following step:

    And I wait for "10000"

where `10000` is the number of millisecods you wish the session to wait.
It is very useful when you want to look at the error or developer console
inside the browser or if you want to interact with the session page manually.

## Useful resources

* [SilverStripe CMS architecture](http://doc.silverstripe.org/sapphire/en/trunk/reference/cms-architecture)
* [SilverStripe Framework Test Module](https://github.com/silverstripe-labs/silverstripe-frameworktest)
* [SilverStripe Unit and Integration Testing](http://doc.silverstripe.org/sapphire/en/trunk/topics/testing)
