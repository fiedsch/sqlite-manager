#!/bin/bash

# Zentral installiertes PHPUnit
# PHPUNIT=phpunit
# Zentral aus dem vendor Verzeichnis (via dev-dependency)
PHPUNIT=vendor/phpunit/phpunit/phpunit

# phpunit ohne xml-config-file, dafür aber mit Kommandozeilenoptionen
#
# ${PHPUNIT} \
#   --verbose \
#   --bootstrap ./vendor/autoload.php \
#   --colors=auto \
#   tests/

# phpunit mit xml-config-file ./phpunit.xml

${PHPUNIT}

## EOF
