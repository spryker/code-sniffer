#!/bin/bash
#
# Lint ruleset XML files.
#
# BASH-VERSION  :4.2+
# DEPENDS       :apt-get install wget libxml2-utils

RULESET1="GlueStreamSpecific/ruleset.xml"
RULESET2="Spryker/ruleset.xml"
RULESET3="SprykerStrict/ruleset.xml"

set -e

# Current directory should be repository root
test -r "$RULESET1"
test -r "$RULESET2"
test -r "$RULESET3"

# Check dependency
hash xmllint

# Create temporary directory
mkdir -p tests/tmp

# Download XML schema definition
wget -nv -N -P tests/tmp/ "https://www.w3.org/2012/04/XMLSchema.xsd"

xmllint --noout --schema tests/tmp/XMLSchema.xsd vendor/squizlabs/php_codesniffer/phpcs.xsd
xmllint --noout --schema vendor/squizlabs/php_codesniffer/phpcs.xsd "$RULESET1"
diff -B "$RULESET1" <(XMLLINT_INDENT="    " xmllint --format "$RULESET1")

xmllint --noout --schema vendor/squizlabs/php_codesniffer/phpcs.xsd "$RULESET2"
diff -B "$RULESET2" <(XMLLINT_INDENT="    " xmllint --format "$RULESET2")

xmllint --noout --schema vendor/squizlabs/php_codesniffer/phpcs.xsd "$RULESET3"
diff -B "$RULESET3" <(XMLLINT_INDENT="    " xmllint --format "$RULESET3")
