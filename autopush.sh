#!/bin/bash
cd /var/www/html/pr || exit
git add .
git commit -m "Auto-commit: $(date '+%Y-%m-%d %H:%M:%S')"
git push origin main
