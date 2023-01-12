# Spryker Code Sniffer
[![CI](https://github.com/spryker/code-sniffer/workflows/CI/badge.svg)](https://github.com/spryker/code-sniffer/actions?query=workflow%3ACI+branch%3Amaster)
[![Latest Stable Version](https://poser.pugx.org/spryker/code-sniffer/v/stable.svg)](https://packagist.org/packages/spryker/code-sniffer)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/spryker/code-sniffer/license.svg)](https://packagist.org/packages/spryker/code-sniffer)
[![Total Downloads](https://poser.pugx.org/spryker/code-sniffer/d/total.svg)](https://packagist.org/packages/spryker/code-sniffer)

This sniffer package follows [PSR-2](http://www.php-fig.org/psr/psr-2/) completely and ships with a lot of additional fixers on top (incl. PSR-12).
Please see the Spryker Coding conventions for details.

[List of included sniffs.](docs/sniffs.md)

## Documentation
See **[docs](docs/README.md)**.

Upstream docs: [squizlabs/PHP_CodeSniffer/wiki](https://github.com/squizlabs/PHP_CodeSniffer/wiki)

## Usage

### How to use in Spryker projects
Make sure you include the sniffer as `require-dev` dependency:
```
composer require --dev spryker/code-sniffer
```

The Development module provides a convenience command:
```
console code:sniff:style
```
(or `console c:s:s` as shortcut)

To automatically fix fixable errors, use
```
console code:sniff:style -f
```

`-v` is useful for more info output.
To run only a specific sniff, use the `-s` option. See `-h` for help.

You can also sniff a specific project level module or path:
```
console code:sniff:style [-m ModuleName] [optional-sub-path] -v
```

### How to use in any project
You can also manually invoke the phpcs/phpcbf commands:
```
vendor/bin/phpcs --standard=vendor/spryker/code-sniffer/Spryker/ruleset.xml ./
vendor/bin/phpcbf --standard=vendor/spryker/code-sniffer/Spryker/ruleset.xml ./
```
The command `phpcs` just sniffs, `phpcbf` fixes.

You probably want to ignore some folders, e.g. `--ignore=vendor/` or some of your test fixture folders.

### Standards
You can always switch the standard to the stricter one named `SprykerStrict`.
It is an extension of the `Spryker` standard with its own (strict) sniffs added on top.

### How to include in your IDE
E.g. for PHPStorm:
* Open Settings -> Tools -> External Tools
* Add a new tool named "cs-sniffer" and set Program to `$ProjectFileDir$/vendor/bin/phpcs`, Parameters to `--standard=$ProjectFileDir$/vendor/spryker/code-sniffer/Spryker/ruleset.xml -p $FilePath$` and Working directory to `$ProjectFileDir$`.
* Add a new tool named "cs-fixer" and set Program to `$ProjectFileDir$/vendor/bin/phpcbf`, Parameters to `--standard=$ProjectFileDir$/vendor/spryker/code-sniffer/Spryker/ruleset.xml -v $FilePath$` and Working directory to `$ProjectFileDir$`.
* Remove the "Open console" if you don't want to see any output here for the fixer.
* Now set up your hotkeys under Settings -> Keymap (search for cs-sniffer and cs-fixer). E.g. `Control + Comma` for sniffing, and `Control + Dot` for fixing.

You can also set up file watchers, but here you should better only whitelist certain sniffs that only add things and don't remove anything.

### How to configure the default rule set

In order to simplify command line interface, `phpcs` allows to specify [default rule set](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-default-coding-standard) in and [standards path](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-installed-standard-paths) the following way.

Assuming the following directory structure:

```
vendor/spryker/code-sniffer/                          # Base directory
                           |_ Spryker/                # Rule set name
                                      |_ ruleset.xml  # Rule set
```

The base directory and rule set can be used in configuration now.

```
vendor/bin/phpcs --config-set installed_paths vendor/spryker/code-sniffer/
vendor/bin/phpcs --config-set default_standard Spryker
```

You might need to specify full directory path. Now the tools can be used without `--standard` switch.
