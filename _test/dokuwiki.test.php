<?php

/**
 * Tests over DokuWiki function for the Webcode plugin
 *
 * @group plugin_webcode
 * @group plugins
 */
class dokuwiki_plugin_webcode_test extends DokuWikiTest
{

    const pageId = 'PageId';
    const webCodeUniqueId = 'WebCodeUniqueKey';
    const storedContent = 'MyHTMLContent';
    const extension = '.webcode';

    public $cacheKey;
    public $cleanPageId;
    public $cleanWebCodeId;

    function setUp(){
        // The cache system is change unfortunately
        global $conf;
        parent::setUp();
        $conf['cachedir']=DOKU_INC.'/data/cache';
        $this->cleanPageId = cleanID(SELF::pageId);
        $this->cleanWebCodeId = cleanID(SELF::webCodeUniqueId);
        $this->cacheKey = $this->cleanPageId.$this->cleanWebCodeId;
    }

    public function test_key(){
        $this->assertNotEquals($this->cacheKey, 0, 'The key must be not 0');
        $this->assertNotNull($this->cacheKey, 'The key must be not NULL');
    }
    /**
     * Dokuwiki Test to see if I can cache and get cached data
     */
    public function test_StoreCache()
    {

        $cache = new cache($this->cacheKey, SELF::extension);
        $cache->storeCache(SELF::storedContent);
        $this->assertNotNull($cache->cache, "The cache file name must be not NULL. It was (+".$cache->cache.")");
        $this->assertTrue(strpos($cache->cache,DOKU_INC) !== false, "The cache file path must contains DOKU_INC (".DOKU_INC."). It was (".$cache->cache.")");


    }

    public function test_RetrieveCache()
    {

        $cache = new cache($this->cacheKey, SELF::extension);
        // Now we retrieve the content
        $content = $cache->retrieveCache();
        $this->assertEquals($content, SELF::storedContent, 'The stored content that we retrieve must be the same');

    }




    /**
     * From:
     *    - the file indexer_indexing.test.php
     *    - https://www.dokuwiki.org/devel:metadata
     *
     * You can add a meta to the index and lookup the pages with this key
     * You cannot retrieve the meta unfortuntaley
     */
    public function test_metaInIndex(){

        /** @var Doku_Indexer $indexer */
        $indexer = idx_get_indexer();
        $indexMetaWebCodeKey = 'webCodeMetaKey';

        // Add a meta from Webcode
        $indexer->addMetaKeys($this->cleanPageId, $indexMetaWebCodeKey, 'testvalue');

        // Retrieve the page with this meta value
        $query = 'testvalue';
        $this->assertEquals(array($this->cleanPageId), $indexer->lookupKey($indexMetaWebCodeKey, $query));


    }

    /**
     *
     * @see http://www.dokuwiki.org/devel:metadata#functions_to_get_and_set_metadata
     *
     */
    public function test_metaAddAndGet(){

        // The list of WebCode hash of a page
        // One hash is one webCode node
        $metaToSave = array('keyCacheId1', 'keyCacheId2');

        // The meta key
        $metaKey = 'WebCodeIds';

        // Set
        $meta = array($metaKey => $metaToSave);
        p_set_metadata($this->cleanPageId, $meta, false, true);

        // Retrieve the meta
        $meta_get = p_get_metadata($this->cleanPageId, $metaKey);

        // Test
        $this->assertEquals($meta_get, $metaToSave, "They must be equals");

    }

}
