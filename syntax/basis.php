<?php
/**
 * Plugin Webcode: Show webcode (Css, HTML) in a iframe
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_webcode_basis extends DokuWiki_Syntax_Plugin
{

    /**
     * @var array that holds the iframe attributes
     */
    private $attributes = array();
    /**
     * @var array That holds the code parts
     */

    private $codes = array();

    /*
     * What is the type of this plugin ?
     * This a plugin categorization
     * This is only important for other plugin
     * See @getAllowedTypes
     */
    public function getType()
    {
        return 'protected';
    }


    // Sort order in which the plugin are applied
    public function getSort()
    {
        return 158;
    }

    // This where the addEntryPattern must bed defined
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<webcode.*?>(?=.*?</webcode>)', $mode, 'plugin_webcode_'.$this->getPluginComponent());
    }

    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</webcode>', 'plugin_webcode_'.$this->getPluginComponent());
    }


    /**
     * Handle the match
     * You get the match for each pattern in the $match variable
     * $state says if it's an entry, exit or match pattern
     *
     * This is an instruction block and is cached apart from the rendering output
     * There is two caches levels
     * This cache may be suppressed with the url parameters ?purge=true
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {

            case DOKU_LEXER_ENTER :

                $match = utf8_substr($match, 8, -1); //9 = strlen("<webcode")

                $this->attributes['frameborder'] = 0;
                // /i not case sensitive
                $attributePattern = "\\s*(\w+)\\s*=\\s*\"?(\\d+(\%|px)?)\"?\\s*";
                $result = preg_match_all('/' . $attributePattern . '/i', $match, $matches);

                if ($result != 0) {
                    foreach ($matches[1] as $key => $codeName) {
                        $this->attributes[strtolower($codeName)] = $matches[2][$key];
                    }
                }

                $extractData = $this->attributes;
                $xhtmlWebCode = $match; // Needed only for debug purpose

                break;

            case DOKU_LEXER_UNMATCHED :

                $codePattern = "\<code\\s*(\\w+)\\s*\>(.+?)\<\/code\>";
                $result = preg_match_all('/' . $codePattern . '/is', $match, $matches, PREG_PATTERN_ORDER);

                if ($result != 0) {
                    foreach ($matches[1] as $key => $codeName) {
                        $this->codes[strtolower($codeName)] = $matches[2][$key];
                    }
                }

                $extractData = $this->codes;

                // Render the whole
                $instructions = p_get_instructions($match);
                $xhtmlWebCode = p_render('xhtml',$instructions,$info);
                break;
        }

        // Cache the values
        return array($state,$xhtmlWebCode,$extractData);
    }

    /**
     * Create output
     * The rendering process
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // The $data variable comes from the handle() function
        //
        // $mode = 'xhtml' means that we output html
        // There is other mode such as metadata where you can output data for the headers (Not 100% sure)
        if ($mode == 'xhtml') {

            list($state, $xhtmlWebCode, $extractData) = $data;

            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $stateDesc = 'DOKU_LEXER_ENTER';
                    // Get back the cached value
                    $this->attributes = $extractData;
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $stateDesc = 'DOKU_LEXER_UNMATCHED';
                    // Get back the cached value
                    $this->codes = $extractData;
                    $renderer->doc .= $xhtmlWebCode;
                    break;
                case DOKU_LEXER_EXIT :
                    $stateDesc = 'DOKU_LEXER_EXIT';

                    if ( array_key_exists('html', $this->codes)) {
                        $htmlContent = $this->codes['html'];
                    } else {
                        $htmlContent = $this->codes['xml'];
                    }

                    $iframeHtml = '<iframe ';
                    foreach ($this->attributes as $key => $attribute) {
                        $iframeHtml = $iframeHtml.' '.$key.'='.$attribute;
                    }
                    $iframeHtml = $iframeHtml.' srcdoc="<style>'.$this->codes['css'].'</style>'.$htmlContent.'"></iframe>';
                    $renderer->doc .= '<P>'.$iframeHtml.'</P>';
                    break;
            }

            return true;
        }
        return false;
    }

}
