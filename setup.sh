#!/bin/bash
# Make sure this file is executable
# chmod +x setup.sh

# Download composer phar if not available yet
[ ! -f composer.phar ] && curl -sS https://getcomposer.org/installer | php
