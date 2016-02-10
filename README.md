# Spryker Code Sniffer
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/spryker/code-sniffer/license.svg)](https://packagist.org/packages/spryker/code-sniffer)
[![Total Downloads](https://poser.pugx.org/spryker/code-sniffer/d/total.svg)](https://packagist.org/packages/spryker/code-sniffer)

## Documentation:
https://github.com/squizlabs/PHP_CodeSniffer/wiki

## Usage in Spryker projects
Make sure you include the sniffer as `require-dev` dependency:

    composer require --dev spryker/code-sniffer

The Development bundle provides a convenience command:

    vendor/bin/console code:sniff
    
To automatically fix fixable errors, use
    
    vendor/bin/console code:sniff -f
    
`-v` is useful for more info output. 
To run only a specific sniff, use the `-s` option.    
    
You can also manually invoke the phpcs/phpcbf commands:
    
    vendor/bin/phpcs --standard=vendor/spryker/code-sniffer/Spryker/ruleset.xml    

## Writing new sniffs
Add them to the corresponding category inside Sniffs folder and add tests in `tests` with the same folder structure.

To run all sniffs on themselves, use

    vendor/bin/phpcs --standard=Spryker/ruleset.xml ./Spryker/Sniffs -v -s

Don't forget to test your changes:

    php phpunit.phar

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
