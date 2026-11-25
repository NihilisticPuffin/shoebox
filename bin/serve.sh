#!/usr/bin/env sh
# Local dev server
cd "$(dirname "$0")/.." || exit 1
exec php \
  -d upload_max_filesize=10M \
  -d post_max_size=200M \
  -d max_file_uploads=20 \
  -d memory_limit=512M \
  -S "${HOST:-localhost}:${PORT:-8000}" -t public public/index.php
