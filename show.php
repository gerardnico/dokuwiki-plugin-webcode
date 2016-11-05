<?php
//
// Deprecated
//
// Was one used to retrieve the HTML data from the cache
// Is nog there for History purpose
//
// A file that permits to create an URL for the IFrame in order to serve
// the file created with the code
//
// with the following URL
// DOKUWIKI_BASE/lib/plugins/webcode/show.php?id=page_id&web_code_id=webcode_id
//
// based on feed.php cache mechanism
//
if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_INC.'inc/init.php');

// The page where the code is written
$ID   = cleanID($INPUT->str('id'));
// The Id of the webcode in the page
$WEB_CODE_ID  = cleanID($INPUT->str('web_code_id'));

//close session (Why?)
session_write_close();

if($conf['allowdebug'] && $INPUT->has('debug')){
    print '<pre>';
    print 'Debug Information: ID ('+$ID+'), WEB_CODE_ID ('+$WEB_CODE_ID+")";
    print '</pre>';
}

// check file permissions
$AUTH = auth_quickaclcheck($ID);
if($AUTH < AUTH_READ){
    // no auth
    print p_locale_xhtml('denied');
} else {

    //start output
    header('Content-Type: text/html; charset=utf-8');
    $key = $ID . $WEB_CODE_ID;
    $cache = new cache($key, '.webcode');
    print $cache->retrieveCache();

}

