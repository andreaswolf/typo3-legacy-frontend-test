#!/usr/bin/env bash

cleanup() {
    git clean -df
}

composer_update() {
    echo "Update Instance"
    composer install

    echo "restore composer.json"
    git restore composer.json
}

update_v11() {
    echo "Add Dev-Requires for v11"
    composer req \
        typo3/cms-core:^11.5 \
        typo3/cms-frontend:^11.5 \
            --no-update
}

case "$1" in
11)
    cleanup
    update_v11
    composer_update
    ;;
*)
    echo "Usage: ddev update-to {9|10|11}" >&2
    exit 0
    ;;
esac
