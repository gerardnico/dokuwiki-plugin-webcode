<?php

if (!defined('DW_LF')) {
    define('DW_LF', "\n");
}

/**
 * Test the component plugin
 *
 * @group plugin_webcode
 * @group plugins
 */
class dokuwiki_plugin_webcode_basis_test extends DokuWikiTest
{

    const PLUGIN_NAME = 'webcode';
    protected $pluginsEnabled = array(self::PLUGIN_NAME);

    function setUp(){
        global $conf;

        parent::setUp();

        // No login to not have the edit button in the output
        $conf['useacl'] = 0;
    }



    /**
     * With no code block, we should get noiframe
     * even if there is webcode
     */
    public function test_no_code_webcode() {


        $content='Teaser content';
        $id = 'test:webcode:nowebcode';
        saveWikiText($id,'<'.self::PLUGIN_NAME.'>'
            .DW_LF
            .'==== Header ===='
            .DW_LF
            .$content
            .DW_LF
            .'</'.self::PLUGIN_NAME.'>','First');

        $testRequest = new TestRequest();
        $testResponse = $testRequest->get(array('id' => $id));
        print($testResponse->getContent());
        $iframe = $testResponse->queryHTML('iframe' );
        $this->assertEquals(0, $iframe->length);

    }

    /**
     * With one code block we should get an iframe
     */
    public function test_one_code_block_webcode() {


        $content='Teaser content';
        $id = 'test:webcode:nowebcode';
        saveWikiText($id,'<'.self::PLUGIN_NAME.'>'
            .DW_LF
            .'==== Header ===='
            .DW_LF
            .$content
            .'<code><p>Hello World</p></code>'
            .DW_LF
            .'</'.self::PLUGIN_NAME.'>','First');

        $testRequest = new TestRequest();
        $testResponse = $testRequest->get(array('id' => $id));
        print($testResponse->getContent());
        $iframe = $testResponse->queryHTML('iframe' );
        $this->assertEquals(1, $iframe->length);

    }

    /**
     * With two webcode, we should get two iframes
     */
    public function test_two_webcode() {

        $content='Teaser content';
        $id = 'test:webcode:twowebcode';
        $webcode = '<' . self::PLUGIN_NAME . '>'
            . DW_LF
            . '==== Header ===='
            . DW_LF
            . $content
            . '<code><p>Hello World</p></code>'
            . DW_LF
            . '</' . self::PLUGIN_NAME . '>';
        saveWikiText($id, $webcode.$webcode,'First');

        $testRequest = new TestRequest();
        $testResponse = $testRequest->get(array('id' => $id));
        print($testResponse->getContent());
        $iframe = $testResponse->queryHTML('iframe' );
        $this->assertEquals(2, $iframe->length);

    }



}
