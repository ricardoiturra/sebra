#!/bin/sh
set -eu

inotifywait --monitor --quiet --event close_write,moved_to --format '%f' graphify-out |
    while IFS= read -r file; do
        if [ "$file" = graph.json ]; then
            php artisan graph:notify
        fi
    done
