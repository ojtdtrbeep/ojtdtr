<?php
/**
 * Railway API — connects to InfinityFree's MySQL remotely.
 *
 * Set these as Environment Variables in Railway dashboard:
 *   DB_HOST     → your InfinityFree MySQL server  e.g. sql123.infinityfree.com
 *   DB_USER     → your InfinityFree MySQL username
 *   DB_PASS     → your InfinityFree MySQL password
 *   DB_NAME     → your InfinityFree database name  e.g. if0_42574085_dtr_system
 *   API_KEY     → MSWDO_OJT_DTR_@2026#SecureKey!
 */

define('DB_HOST', getenv('DB_HOST') ?: 'sql123.infinityfree.com');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('API_KEY', getenv('API_KEY') ?: 'MSWDO_OJT_DTR_@2026#SecureKey!');

date_default_timezone_set('Asia/Manila');
