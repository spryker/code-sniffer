# Spryker Code Sniffer
[![CI](https://github.com/spryker/code-sniffer/workflows/CI/badge.svg)](https://github.com/spryker/code-sniffer/actions?query=workflow%3ACI+branch%3Amaster)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/spryker/code-sniffer/license.svg)](https://packagist.org/packages/spryker/code-sniffer)
[![Total Downloads](https://poser.pugx.org/spryker/code-sniffer/d/total.svg)](https://packagist.org/packages/spryker/code-sniffer)

This sniffer package follows [PSR-2](http://www.php-fig.org/psr/psr-2/) completely and ships with a lot of additional fixers on top (incl. PSR-12).
Please see the Spryker Coding conventions for details.

[List of included sniffs.](/docs)

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

Make sure that you include the Spryker core standard ruleset in your custom one, e.g.:
```xml
<?xml version="1.0"?>
<ruleset name="SprykerProject">
    <description>
        Spryker Coding Standard for Project.
        Extends main Spryker Coding Standard.
        All sniffs in ./Sniffs will be auto loaded
    </description>

    <rule ref="vendor/spryker/code-sniffer/Spryker/ruleset.xml"/>

    <exclude-pattern>*/src/Generated/*</exclude-pattern>
    <exclude-pattern>*/src/Orm/*</exclude-pattern>
    <exclude-pattern>*/tests/_support/_generated/*</exclude-pattern>
    <exclude-pattern>*/tests/_helpers/*</exclude-pattern>
    <exclude-pattern>*/tests/_output/*</exclude-pattern>
    <exclude-pattern>./data/DE/*</exclude-pattern>

    <!-- Define your own sniffs here -->
</ruleset>
```
If you want to use the `SprykerStrict` standard in your project, you should replace the string:
```xml
<rule ref="vendor/spryker/code-sniffer/Spryker/ruleset.xml"/>
```
with this one:
```xml
<rule ref="vendor/spryker/code-sniffer/SprykerStrict/ruleset.xml"/>
```

## Custom licensing
You can provide a custom license via `.license` file in your repository root.
It must be a PHP doc block (valid PHP) including a trailing new line.
You can also leave the file empty to have "no license doc block".

For MIT repositories we recommend (having a `LICENSE` file provided in your root, as well):
```
/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */
```
The 2nd sentence can be customized to your needs.

## Integrating into CI testing and PRs
The following is an example for CircleCI but will also be compatible with any other CI system:
```
  override:
    ...
    - vendor/bin/console application:integration-check
    - vendor/bin/console code:sniff:style
    - vendor/bin/console code:sniff:architecture
```
You basically just add `- vendor/bin/console code:sniff:style` the the list.

Please see the [Spryker Suite](https://github.com/spryker-shop/suite) project repository for details. It is used there.

## Migration tips

When migrating code sniffer for larger repositories with many developers working on it, there are a few guidelines for flawless migrations:

- Make always deliberate and scheduled code sniffer updates (lock down the sniffer to patch releases using `~` or even a specific version if needed).
- Don't update code sniffer in any feature/bugfix branch, never run updated sniff rules on all branches.
- Run updated sniffer ruleset on a branched master (e.g. master-cs) first and here update the lock file for it using `composer require/update`.
- Once this one is merged, then apply those into the feature and bugfix branches using `git merge origin/master` and apply then the new coding standard on the newly written code on top.
- This way all project branches only fail on CS after this delibare update, and never by accident.

### Excluding core sniffs
Note that you are never forced to adapt the whole standard changes at once (even though recommended).
You can, for migration purposes, also exclude/silence certain sniffs on project level, if that helps.
At the same time, you can also further stricten them or add additional ones and let us know about them (and their usefuleness) via issue.

See CS sniffer docs for details, but in general using `severity` of `0` can silence a rule or a subset of it:
```xml
<rule ref="SlevomatCodingStandard.ControlStructures.ControlStructureSpacing">
    <severity>0</severity>
</rule>
```
This deactives the whole ControlStructureSpacing sniff.

## Excluding test related comparison files
If you want to exclude certain generated (e.g. PHP) files, make sure those are in a `test_files` subfolder to be auto-skipped.
You can otherwise always create a custom and rather unique folder name and manually exclude it in your PHPCS settings.

## Writing new sniffs
Add them to the corresponding category inside Sniffs folder and add tests in `tests` with the same folder structure.

To run all sniffs on themselves, use inside this sniffer repository root:
```
composer cs-check
```

Don't forget to test your changes:
```
composer test
```

Also run `composer docs` to generate new sniff list.

Note: To get those scripts above working from this repository root you need to run `composer update` first.

#### Tokenizing Tool
When coding new sniffs it really helps to see what the code looks like in regards of the token arrays.
So we can parse a PHP file into its tokens using the following tool:

```
bin/tokenize /path/to/file
```
(If you run this from your application, it will have to be run as `vendor/bin/tokenize`)

With more verbose output:
```
bin/tokenize /path/to/file -v
```

For a file `MyClass.php` it will create a token file `MyClass.tokens.php` in the same folder.

Example output of a single line of PHP code:
```php
...
    protected static function _optionsToString($options) {
// T_WHITESPACE T_PROTECTED T_WHITESPACE T_STATIC T_WHITESPACE T_FUNCTION T_WHITESPACE T_STRING T_OPEN_PARENTHESIS T_VARIABLE T_CLOSE_PARENTHESIS T_WHITESPACE T_OPEN_CURLY_BRACKET T_WHITESPACE
...
```
Using the verbose option:
```php
...
    protected static function _optionsToString($options) {
// T_WHITESPACE (935) code=379, line=105, column=1, length=1, level=1, conditions={"9":358}, content=`\t`
// T_PROTECTED (936) code=348, line=105, column=2, length=9, level=1, conditions={"9":358}, content=`protected`
// T_WHITESPACE (937) code=379, line=105, column=11, length=1, level=1, conditions={"9":358}, content=` `
// T_STATIC (938) code=352, line=105, column=12, length=6, level=1, conditions={"9":358}, content=`static`
// T_WHITESPACE (939) code=379, line=105, column=18, length=1, level=1, conditions={"9":358}, content=` `
// T_FUNCTION (940) code=337, line=105, column=19, length=8, parenthesis_opener=943, parenthesis_closer=945, parenthesis_owner=940, scope_condition=940, scope_opener=947, scope_closer=1079, level=1, conditions={"9":358}, content=`function`
// T_WHITESPACE (941) code=379, line=105, column=27, length=1, level=1, conditions={"9":358}, content=` `
// T_STRING (942) code=310, line=105, column=28, length=16, level=1, conditions={"9":358}, content=`_optionsToString`
// T_OPEN_PARENTHESIS (943) code=PHPCS_T_OPEN_PARENTHESIS, line=105, column=44, length=1, parenthesis_opener=943, parenthesis_owner=940, parenthesis_closer=945, level=1, conditions={"9":358}, content=`(`
// T_VARIABLE (944) code=312, line=105, column=45, length=8, nested_parenthesis={"943":945}, level=1, conditions={"9":358}, content=`$options`
// T_CLOSE_PARENTHESIS (945) code=PHPCS_T_CLOSE_PARENTHESIS, line=105, column=53, length=1, parenthesis_owner=940, parenthesis_opener=943, parenthesis_closer=945, level=1, conditions={"9":358}, content=`)`
// T_WHITESPACE (946) code=379, line=105, column=54, length=1, level=1, conditions={"9":358}, content=` `
// T_OPEN_CURLY_BRACKET (947) code=PHPCS_T_OPEN_CURLY_BRACKET, line=105, column=55, length=1, bracket_opener=947, bracket_closer=1079, scope_condition=940, scope_opener=947, scope_closer=1079, level=1, conditions={"9":358}, content=`{`
// T_WHITESPACE (948) code=379, line=105, column=56, length=0, level=2, conditions={"9":358,"940":337}, content=`\n`
...
```

### Running own sniffs on this project
There is a convenience script to run all sniffs for this repository:
```
composer cs-check
```
If you want to fix the fixable errors, use
```
composer cs-fix
```
Once everything is green you can make a PR with your changes.
