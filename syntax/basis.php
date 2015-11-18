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

    /**
     * Print the output of the console javascript function ?
     */
    private $useConsole = false;

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
        $this->Lexer->addEntryPattern('<webcode.*?>(?=.*?</webcode>)', $mode, 'plugin_webcode_' . $this->getPluginComponent());
    }

    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</webcode>', 'plugin_webcode_' . $this->getPluginComponent());
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

                // We got the first webcode tag and its attributes

                $match = utf8_substr($match, 8, -1); //9 = strlen("<webcode")

                // Reset of the attributes
                // With some framework the php object may be still persisted in memory
                // And you may get some attributes from other page
                $this->attributes= array();
                $this->attributes['frameborder'] = 1;
                $this->attributes['width'] = '100%';

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

                // We got the content between the webcode tag and its attributes

                // We parse it in order to extract the code.

                // Reset of the variable
                // With some framework the php object may be still persisted in memory
                // And you may get some variable from other page
                $this->codes = array();
                $this->useConsole = false;

                // Regexp Pattern
                $codePattern = "\<code\\s*(\\w+)\\s*\>(.+?)\<\/code\>";
                $result = preg_match_all('/' . $codePattern . '/is', $match, $matches, PREG_PATTERN_ORDER);
                if ($result != 0) {
                    foreach ($matches[1] as $key => $codeName) {
                        // No double quote because the code will goes in the srcdoc attribute of the iframe element
                        $code = str_replace('"', '\'', $matches[2][$key]);
                        $this->codes[strtolower($codeName)] = $code;

                        if (strtolower($codeName)=='javascript'){
                            // if the code contains 'console.'
                            $result = preg_match('/' .'console.'. '/is',$code);
                            if ($result) {
                                $this->useConsole = true;
                            } else  {
                                $this->useConsole = false;
                            }

                        }
                    }
                }

                // If their is xml without html code, xml becomes html code
                if (!array_key_exists('html', $this->codes)) {
                    if (array_key_exists('xml', $this->codes)) {
                        $this->codes['html'] = $this->codes['xml'];
                        unset($this->codes['xml']);
                    }
                }
                $extractData = $this->codes;

                // Render the whole
                $instructions = p_get_instructions($match);
                $xhtmlWebCode = p_render('xhtml', $instructions, $info);
                break;
        }

        // Cache the values
        return array($state, $xhtmlWebCode, $extractData);
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
                    // The extracted data are the attribute of the webcode tag
                    $this->attributes = $extractData;
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $stateDesc = 'DOKU_LEXER_UNMATCHED';
                    // The extracted data are the code for this step
                    $this->codes = $extractData;
                    // Add the wiki output between the two webcode tag
                    $renderer->doc .= $xhtmlWebCode;
                    break;
                case DOKU_LEXER_EXIT :
                    $stateDesc = 'DOKU_LEXER_EXIT';

                    // We add the Iframe and the JsFiddleButton
                    $iframeHtml = '<iframe ';
                    foreach ($this->attributes as $key => $attribute) {
                        $iframeHtml = $iframeHtml . ' ' . $key . '=' . $attribute;
                    }
                    $iframeHtml .= ' srcdoc="<html><head>';
                    $iframeHtml .= '<meta http-equiv=\'content-type\' content=\'text/html; charset=UTF-8\'>';
                    $iframeHtml .= '<title>Made by Webcode</title>';
                    $iframeHtml .= '<link rel=\'stylesheet\' type=\'text/css\' href=\'https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css\'>';

                    // Jquery ?
                    if (array_key_exists('javascript',$this->codes)) {
                        // JQuery is used for the console facility
                        $iframeHtml .= '<script type=\'text/javascript\' src=\'http://code.jquery.com/jquery-2.1.3.min.js\'></script>';
                    }

                    // WebConsole
                    if ($this->useConsole) {
                        $iframeHtml .= '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . DOKU_URL . 'lib/plugins/webcode/webCodeConsole.css\'></link>';
                    }

                    if (array_key_exists('css',$this->codes)) {
                        $iframeHtml .= '<!-- The CSS code -->';
                        $iframeHtml .= '<style>'.$this->codes['css'] . '</style>';
                    };
                    $iframeHtml .= '</head><body style=\'margin:10px\'>';
                    if (array_key_exists('html',$this->codes)){
                        $iframeHtml .= '<!-- The HTML code -->';
                        $iframeHtml .=  $this->codes['html'];
                    }
                    // The javascript console area is based at the end of the HTML document
                    if ($this->useConsole) {
                        $iframeHtml .= '<!-- WebCode Console -->';
                        $iframeHtml .= '<div id=\'webCodeConsole\'></div>';
                        $iframeHtml .= '<script type=\'text/javascript\' src=\''.DOKU_URL.'lib/plugins/webcode/webCodeConsole.js\'></script>';
                    }
                    if (array_key_exists('javascript',$this->codes)){
                        $iframeHtml .= '<!-- The Javascript code -->';
                        $iframeHtml .=  '<script>'.$this->codes['javascript'] . '</script>';
                    }
                    $iframeHtml .= '</body></html>"></iframe>';

                    $renderer->doc .= '<div>' . $this->addJsFiddleButton($this->codes) . $iframeHtml . '</div>';


                    break;
            }

            return true;
        }
        return false;
    }

    /**
     * @param $codes the array containing the codes
     * @return string the HTML form code
     */
    public function addJsFiddleButton($codes)
    {

        // From http://doc.jsfiddle.net/api/post.html

        $postURL = "https://jsfiddle.net/api/post/library/pure/"; //No Framework
        if (array_key_exists('javascript',$this->codes)){
            $postURL = "http://jsfiddle.net/api/post/jQuery/2.1.3/";
        }

        $jsFiddleButtonHtmlCode =
            '<div class="webcodeButton">' .
            '<form method="post" action="'.$postURL.'" target="_blank">' .
            '<input type="hidden" name="title" value="Title">' .
            '<input type="hidden" name="css" value="' . $codes['css'] . '">' .
            '<input type="hidden" name="html" value="' . $codes['html'] . '">' .
            '<input type="hidden" name="js" value="' . $codes['javascript'] . '">' .
            '<input type="hidden" name="wrap" value="b">' .  //javascript no wrap in body
            '<button class="btn btn-link">'.$this->getLang('JsFiddleButtonContent').'</button>' .
            '</form>'.
            '</div>';

        return $jsFiddleButtonHtmlCode;

    }

}
