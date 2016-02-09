# Spryker Code Sniffer

## Documentation:
https://github.com/squizlabs/PHP_CodeSniffer/wiki

## Usage in Spryker projects
Make sure you include the sniffer as `require-dev` dependency:

    composer require --dev spryker/spryker-codesniffer

The Development bundle provides a convenience command:

    vendor/bin/console code:test
    
To automatically fix fixable errors, use
    
    vendor/bin/console code:test -f
    
`-v` is useful for more info output. 
To run only a specific sniff, use the `-s` option.    
    
You can also manually invoke the phpcs/phpcbf commands:
    
    vendor/bin/phpcs --standard=vendor/spryker/spryker-codesniffer/Spryker/ruleset.xml    

## Writing new sniffs
Add them to the corresponding category inside Sniffs folder and add tests in `tests` with the same folder structure.

To run all sniffs on themselves, use

    vendor/bin/phpcs --standard=Spryker/ruleset.xml ./Spryker/Sniffs -v -s

Don't forget to test your changes:

    php phpunit.phar
