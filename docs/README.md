# Documentation

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

## Custom repositories
If you want to check the namespaces for your own custom repositories, you might have to adjust
the namespace sniff config:
```xml
    <rule ref="Spryker.Namespaces.SprykerNamespace">
        <properties>
            <property name="namespace" value="App"/>
            <property name="isRoot" value="true"/>
        </properties>
    </rule>
```
It would then validate your class files within `src/`:
- file name expected to be `src/Some/Sub/MyClass.php`
- FQCN to be `App\\Some\\Sub\\MyClass`

If you do not customize anything here, this sniff will only run through Spryker namespaces/folders,
and will be ignored for any other repository structure.

In some rare cases, you might also need `rootDir` config, e.g. when `src/` is not your
default root directory:
```xml
        <properties>
            <property name="rootDir" value="custom"/>
        </properties>
```

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

### Silencing core rules or parts
Note that you are never forced to adapt the whole standard changes at once (even though recommended).
You can, for migration purposes, also exclude/silence certain sniffs on project level, if that helps.
At the same time, you can also further stricten them or add additional ones and let us know about them (and their usefuleness) via issue.

See CS sniffer docs for details, but in general using `severity` of `0` can silence a rule or a subset of it:
```xml
<!-- full silence: x.y.z -->
<rule ref="SlevomatCodingStandard.ControlStructures.ControlStructureSpacing">
    <severity>0</severity>
</rule>

<!-- partial silence: x.y.z.code -->
<rule ref="SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName">
    <severity>0</severity>
</rule>
```

### Excluding certain sniffs
You can also completely exclude certain sniffs, e.g. if you are on a PHP 8+ project and
want to use all the new language features:
```xml
<rule ref="vendor/spryker/code-sniffer/Spryker/ruleset.xml">
    <exclude name="SlevomatCodingStandard.Functions.DisallowNamedArguments"/>
    <exclude name="SlevomatCodingStandard.Functions.DisallowTrailingCommaInDeclaration"/>
    <exclude name="SlevomatCodingStandard.Classes.DisallowConstructorPropertyPromotion"/>
    <exclude name="SlevomatCodingStandard.ControlStructures.DisallowNullSafeObjectOperator"/>
    ...
</rule>
```
They are shipped by default to avoid PHP8-creep into PHP7.4+ code.

## Configure custom namespaces
Certain sniffs rely on a list of namespaces, which defaults to `Pyz,SprykerEco,SprykerMiddleware,SprykerSdk,Spryker`, but can be customized like so:
```xml
    <rule ref="Spryker.MethodAnnotation.ConfigMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
    <rule ref="Spryker.MethodAnnotation.EntityManagerMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
    <rule ref="Spryker.MethodAnnotation.FacadeMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
    <rule ref="Spryker.MethodAnnotation.FactoryMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
    <rule ref="Spryker.MethodAnnotation.QueryContainerMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
    <rule ref="Spryker.MethodAnnotation.RepositoryMethodAnnotation">
        <properties>
            <property name="namespaces" value="MyCustomPyz,SprykerEco,Spryker" />
        </properties>
    </rule>
```

## Customize PHP version safety

If you want to enable `Spryker.Internal.SprykerDisallowFunctions` for your project level, set this into your `phpcs.xml` file:
```xml
    <rule ref="Spryker.Internal.SprykerDisallowFunctions">
        <properties>
            <property name="phpVersion" value="8.1"/>
        </properties>
    </rule>
```
Set the current PHP version you are using which will disallow methods of the next minors and majors.
If you already require certain polyfills, you can raise this version or completely disable it (even the core check) by using `'off'` value.

## Enabling `strict_types`
Projects can - at their own discretion - enable strict mode for PHP files:
```xml
    <rule ref="Spryker.PHP.DeclareStrictTypesAfterFileDoc">
        <properties>
            <property name="strictTypesMandatory" value="true"/>
        </properties>
    </rule>
```
Please note: This can have side effects as type casting is now not happening anymore in some cases.

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

### Testing your sniff
To add tests you can quickly generate the necessary files using
```
php tests/generate.php MyNamespace.MyType.MySniffName
```
You can also use `"FQCN"` of the sniff instead (".." quotes are important as the namespace backslashes would get lost otherwise).

Tip: When running it without argument, it shows you the sniffs that are yet untested.

### Tokenizing Tool
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
