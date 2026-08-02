#!/bin/sh
set -eu

export APP_URL="${APP_URL:-http://127.0.0.1:8080}"

exec /Applications/XAMPP/xamppfiles/bin/php \
    -d mysqli.default_socket=/tmp/mysql.sock \
    -d pdo_mysql.default_socket=/tmp/mysql.sock \
    -S 127.0.0.1:8080 \
    -t php
