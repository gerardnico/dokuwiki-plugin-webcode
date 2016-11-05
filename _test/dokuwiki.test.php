<?php

/**
 * Tests over DokuWiki function for the Webcode plugin
 *
 * @group plugin_webcode
 * @group plugins
 */
class dokuwiki_plugin_webcode_test extends DokuWikiTest
{

    const  pageId = 'PageId';
    const  webCodeUniqueId = 'WebCodeUniqueKey';
    const storedContent = 'MyHTMLContent';
    const extension = '.webcode';


    function setUp(){
        // The cache system is change unfortunately
        global $conf;
        parent::setUp();
        $conf['cachedir']=DOKU_INC.'/data/cache';
    }

    /**
     * Dokuwiki Test to see if I can cache and get cached data
     */
    public function test_StoreCache()
    {

        $key = SELF::getKey();
        $this->assertNotEquals($key, 0, 'The key must be not 0');
        $this->assertNotNull($key, 'The key must be not NULL');

        $cache = new cache($key, SELF::extension);
        $cache->storeCache(SELF::storedContent);
        $this->assertNotNull($cache->cache, "The cache file name must be not NULL. It was (+".$cache->cache.")");
        $this->assertTrue(strpos($cache->cache,DOKU_INC) !== false, "The cache file path must contains DOKU_INC (".DOKU_INC."). It was (".$cache->cache.")");


    }

    public function test_RetrieveCache()
    {

        $cache = new cache(SELF::getKey(), SELF::extension);
        // Now we retrieve the content
        $content = $cache->retrieveCache();
        $this->assertEquals($content, SELF::storedContent, 'The stored content that we retrieve must be the same');

    }

    public function getKey()
    {
        return cleanID(SELF::pageId).cleanID(SELF::webCodeUniqueId);
    }

}
