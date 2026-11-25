<?php
declare(strict_types=1);

const UPLOAD_DIR = ROOT . '/storage/images';
const THUMB_DIR  = ROOT . '/storage/thumbnails';
const MAX_BYTES  = 8 * 1024 * 1024;
const MAX_PIXELS = 40000000;
const GRID       = 41;
const SIZES      = [20, 35, 50, 65, 80, 100];

const MAX_FILES_PER_UPLOAD = 20;
const THUMB_SIZE           = 400;
const PER_PAGE             = 24;
const CONTACT_REASONS      = ['delete_account', 'report_post', 'suggestion', 'other'];
const VISIBILITIES         = ['public', 'unlisted', 'private'];
const ALLOWED_MIMES        = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/gif'  => 'gif',
  'image/webp' => 'webp',
];
