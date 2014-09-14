<?php

/*
 * This file is part of the Social Share Count DB package.
 */

if (php_sapi_name() != 'cli') {
    exit;
}

require_once dirname(__FILE__) . '/../../../wp-load.php';

$wpSocialCache = SocialShareCountDB::getInstance();
$wpSocialCache->requestSocialCount();