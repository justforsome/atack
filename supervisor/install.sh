#!/bin/bash

#
sed "s!\\\$dir\\\$!$(pwd)!" supervisor/atack_worker.conf > /etc/supervisor/conf.d/atack_worker.conf

echo "Applying changes to supervisor..."
supervisorctl reread
supervisorctl update
