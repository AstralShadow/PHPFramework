#!/bin/bash

has_errors=0

for file in $(find -name "*.php"); do
    output=$(
        php -l "$file" |
            grep -v "No syntax errors detected in"
    );

    if [ "$output" != "" ]; then
        has_errors=1
    fi
done

if [ $has_errors -eq 0 ]; then
    echo "No sytanx errors detected"
fi

