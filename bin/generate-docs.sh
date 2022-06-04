#!/usr/bin/env sh

DOCUMENT="$(vendor/bin/phpcs -e --standard=SprykerStrict/ruleset.xml)"

if [ $? -ne 0 ]; then 
    echo 'Invalid execution. Run from ROOT after `composer install` etc. as `composer docs`.'
    exit 1
fi

sed -e 's#^  #- #' -e '1 i# Spryker Code Sniffer' <<<"$DOCUMENT" >docs/README.md

echo "OK."
