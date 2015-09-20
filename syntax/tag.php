<?php
/**
 * A syntax plugin where to start
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
class syntax_plugin_webcode_tag extends DokuWiki_Syntax_Plugin
{

    /*
     * What is the type of this plugin ?
     * This a plugin categorization
     * This is only important for other plugin
     * See @getAllowedTypes
     */
    public function getType()
    {
        return 'container';
    }

    // This function tells the parser to apply or not
    // the content between the start and end tag
    // to the below type plugin
//    public function getAllowedTypes()
//    {
//        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
//    }

    // Sort order in which the plugin are applied
    public function getSort()
    {
        return 158;
    }

    // This where the addEntryPattern must bed defined
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<tag.*?>(?=.*?</tag>)', $mode, 'plugin_webcode_'.$this->getPluginComponent());
    }

    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {
        //$this->Lexer->addPattern('.*(?</tag>?)','plugin_webcode_'.$this->getPluginComponent());
        $this->Lexer->addExitPattern('</tag>', 'plugin_webcode_'.$this->getPluginComponent());
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
        return array($state, $match);
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

            list($state, $match) = $data;


            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $stateDesc = 'DOKU_LEXER_ENTER';
                    $match = $renderer->_xmlEntities($match);
                    break;
                case DOKU_LEXER_MATCHED :
                    $stateDesc = 'DOKU_LEXER_MATCHED';
                    $match = $renderer->_xmlEntities($match);
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $stateDesc = 'DOKU_LEXER_UNMATCHED';
                    $instructions = p_get_instructions($match);
                    $match = p_render($mode,$instructions,$info);
                    break;
                case DOKU_LEXER_EXIT :
                    $stateDesc = 'DOKU_LEXER_EXIT';
                    $match = $renderer->_xmlEntities($match);
                    break;
                case DOKU_LEXER_SPECIAL :
                    $stateDesc = 'DOKU_LEXER_SPECIAL';
                    $match = $renderer->_xmlEntities($match);
                    break;
            }

            $renderer->doc .= "<BR>".$stateDesc.": ".$match;
            return true;
        }
        return false;
    }

}