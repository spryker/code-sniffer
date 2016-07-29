# Spryker Code Sniffer
[![Build Status](https://api.travis-ci.org/spryker/code-sniffer.svg?branch=master)](https://travis-ci.org/spryker/code-sniffer)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/spryker/code-sniffer/license.svg)](https://packagist.org/packages/spryker/code-sniffer)
[![Total Downloads](https://poser.pugx.org/spryker/code-sniffer/d/total.svg)](https://packagist.org/packages/spryker/code-sniffer)

This sniffer package follows [PSR-2](http://www.php-fig.org/psr/psr-2/) and ships with a lot of additional fixers on top.
Please see the Spryker Coding conventions for details.

## Documentation
https://github.com/squizlabs/PHP_CodeSniffer/wiki

## Usage
### How to use in Spryker projects
Make sure you include the sniffer as `require-dev` dependency:
```
composer require --dev spryker/code-sniffer
```

The Development bundle provides a convenience command:
```
vendor/bin/console code:sniff
```

To automatically fix fixable errors, use
```
vendor/bin/console code:sniff -f
```

`-v` is useful for more info output. 
To run only a specific sniff, use the `-s` option. See `-h` for help.   

### How to use in any project
You can also manually invoke the phpcs/phpcbf commands:
```
vendor/bin/phpcs --standard=vendor/spryker/code-sniffer/Spryker/ruleset.xml ./
vendor/bin/phpcbf --standard=vendor/spryker/code-sniffer/Spryker/ruleset.xml ./
```
The command `phpcs` just sniffs, `phpcbf` fixes.

You probably want to ignore some folders, e.g. `--ignore=vendor/` or some of your test fixture folders.

## Using own project standard
You can exchange or extend the Spryker coding standard by providing your own ruleset.xml.
This can be configured in the Development bundle config:

```php
// DevelopmentConfig.php

    /**
     * Either a relative or full path to the ruleset.xml or a name of an installed
     * standard (see `phpcs -i` for a list of available ones).
     *
     * @return string
     */
    public function getCodingStandard()
    {
        return '/path/to/your/ruleset.xml';
    }
```
If you use it for custom projects, just use `--standard` to point to your ruleset file.

## Integrating into CI testing and PRs
The following is an example for CircleCI but will also be compatible with any other CI system:
```
  override:
    ...
    - vendor/bin/console application:integration-check
    - vendor/bin/console code:sniff
```
You basically just append `- vendor/bin/console code:sniff` at the end.


Please see the [Spryker Demoshop](https://github.com/spryker/demoshop) repository for details. It is used there.

## Writing new sniffs
Add them to the corresponding category inside Sniffs folder and add tests in `tests` with the same folder structure.

To run all sniffs on themselves, use
```
vendor/bin/phpcs --standard=Spryker/ruleset.xml Spryker/Sniffs/ -v -s ./
```

Don't forget to test your changes:
```
./setup.sh
php phpunit.phar
```

### Running own sniffs on this project
There is a convenience script to run all sniffs for this repository:
```
./phpcs.sh
```
If you want to fix the fixable errors, use
```
./phpcs.sh -f
```
Once everything is green you can make a PR with your changes.
