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

    protected $pluginsEnabled = array(self::PLUGIN_NAME);


    const PLUGIN_NAME = 'webcode';

    public function test_base_webcode() {

        $info = array();
        $content='Teaser content';
        $instructions = p_get_instructions('<'.self::PLUGIN_NAME.'>'
            .DW_LF
            .'==== Header ===='
            .DW_LF
            .$content
            .DW_LF
            .'</'.self::PLUGIN_NAME.'>');
        $xhtml = p_render('xhtml', $instructions, $info);

        $expected = '<div class="card" style="width: 18rem;">'.$content.'</div>';


        $this->assertEquals($expected, $xhtml);

    }



}
