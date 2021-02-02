<?php
/**
 * Plugin Webcode: Show webcode (Css, HTML) in a iframe
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

// must be run within Dokuwiki
use dokuwiki\Extension\Event;

if (!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_webcode_basis extends DokuWiki_Syntax_Plugin
{

    const EXTERNAL_RESOURCES_ATTRIBUTE_DISPLAY = 'externalResources'; // In the action bar
    const EXTERNAL_RESOURCES_ATTRIBUTE_KEY = 'externalresources'; // In the code

    // Simple cache bursting implementation for the webCodeConsole.(js|css) file
    // They must be incremented manually when they changed
    const WEB_CONSOLE_CSS_VERSION = 1.1;
    const WEB_CONSOLE_JS_VERSION = 2.1;

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

    /**
     * @param $mode
     * @param array $instructions
     * @param array $info - the $info of the renderer to pass context information
     * @return string|null
     * Wrapper around {@link p_render()} that pass the $info through tho the created Renderer
     */
    private static function p_render($mode, array $instructions, array &$info)
    {
        if (is_null($instructions)) return '';
        if ($instructions === false) return '';

        $Renderer = p_get_renderer($mode);
        if (is_null($Renderer)) return null;

        $Renderer->reset();
        if (!empty($info)) {
            $Renderer->info = $info;
        }

        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();

        // Loop through the instructions
        foreach ($instructions as $instruction) {
            // Execute the callback against the Renderer
            if (method_exists($Renderer, $instruction[0])) {
                call_user_func_array(array(&$Renderer, $instruction[0]), $instruction[1] ? $instruction[1] : array());
            }
        }

        //set info array
        $info = $Renderer->info;

        // Post process and return the output
        $data = array($mode, & $Renderer->doc);
        Event::createAndTrigger('RENDERER_CONTENT_POSTPROCESS', $data);
        return $Renderer->doc;

    }


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     *
     * container because it may contain header in case of how to
     */
    public function getType()
    {
        // formatting ?
        // container
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * array('container', 'baseonly','formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    public function getAllowedTypes()
    {
        return array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /*
     * Don't accept the code mode
     * in order to get the code block
     * in the DOKU_LEXER_MATCHED state through addPattern
     */
    function accepts($mode)
    {
        if ($mode == "code" || $mode == "plugin_combo_code") {
            return false;
        }
        return parent::accepts($mode);
    }


    /**
     * @see Doku_Parser_Mode::getSort()
     * The mode (plugin) with the lowest sort number will win out
     *
     * See {@link Doku_Parser_Mode_code}
     */
    public function getSort()
    {
        return 100;
    }

    /**
     * Called before any calls to ConnectTo
     * @return void
     */
    function preConnect()
    {
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     *
     * All dokuwiki mode can be seen in the parser.php file
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode)
    {

        $this->Lexer->addEntryPattern('<webcode.*?>(?=.*?</webcode>)', $mode, $this->getPluginMode());

    }


    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {

        /**
         * Capture all code block
         * See {@link Doku_Parser_Mode_code}
         */
        $this->Lexer->addPattern('<code.*?</code>', $this->getPluginMode());

        /**
         * End
         */
        $this->Lexer->addExitPattern('</webcode>', $this->getPluginMode());

    }


    /**
     * Handle the match
     * You get the match for each pattern in the $match variable
     * $state says if it's an entry, exit or match pattern
     *
     * This is an instruction block and is cached apart from the rendering output
     * There is two caches levels
     * This cache may be suppressed with the url parameters ?purge=true
     *
     * The returned values are cached in an array that will be passed to the render method
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {

            case DOKU_LEXER_ENTER :

                // We got the first webcode tag and its attributes

                $match = substr($match, 8, -1); //9 = strlen("<webcode")

                // Reset of the attributes
                // With some framework the php object may be still persisted in memory
                // And you may get some attributes from other page
                $attributes = array();
                $attributes['frameborder'] = 1;
                $attributes['width'] = '100%';

                $renderingModeKey = 'renderingmode';
                $attributes[$renderingModeKey] = 'story';

                // config Parameters will get their value in lowercase
                $configAttributes = [$renderingModeKey];

                // /i not case sensitive
                $attributePattern = "\s*(\w+)\s*=\s*\"?([^\"\s]+)\"?\\s*";
                $result = preg_match_all('/' . $attributePattern . '/i', $match, $matches);


                if ($result != 0) {
                    foreach ($matches[1] as $key => $lang) {
                        $attributeKey = strtolower($lang);
                        $attributeValue = $matches[2][$key];
                        if (in_array($attributeKey, $configAttributes)) {
                            $attributeValue = strtolower($attributeValue);
                        }
                        $attributes[$attributeKey] = $attributeValue;
                    }
                }

                // We set the attributes on a class scope
                // to be used in the DOKU_LEXER_UNMATCHED step
                $this->attributes = $attributes;

                // Cache the values to be used by the render method
                return array($state, $attributes);


            /**
             * The code block as asked
             * by addPattern() into {@link postConnect}
             */
            case DOKU_LEXER_MATCHED:

                $xhtmlWebCode = "";

                // We got the content between the webcode tag and its attributes
                // We parse it in order to extract the code in the codes array
                $codes = array();
                /**
                 * Does the javascript contains a console statement
                 */
                $useConsole = false;

                // Regexp Pattern to parse the codes block
                $codePattern = "<code\s*([^>\s]*)\s*([^>\s]*)>(.+?)<\/code>";
                // The first group is the lang
                // The second group is the file name and options
                // The third group is the code
                $result = preg_match_all('/' . $codePattern . '/msi', $match, $matches, PREG_PATTERN_ORDER);
                if ($result) {

                    // Loop through the block codes
                    foreach ($matches[1] as $key => $lang) {

                        // Get the code (The content between the code nodes)
                        // We ltrim because the match gives us the \n at the beginning and at the end
                        $code = ltrim($matches[3][$key]);

                        // String are in lowercase
                        $lowerCodeName = strtolower($lang);

                        // Xml is html
                        if ($lowerCodeName == 'xml') {
                            $lowerCodeName = 'html';
                        }

                        // If the code doesn't exist in the array, index it otherwise append it
                        if (!array_key_exists($lowerCodeName, $codes)) {
                            $codes[$lowerCodeName] = $code;
                        } else {
                            $codes[$lowerCodeName] = $codes[$lowerCodeName] . $code;
                        }

                        // Check if a javascript console function is used, only if the flag is not set to true
                        if (!$useConsole == true) {
                            if (in_array($lowerCodeName, array('babel', 'javascript', 'html', 'xml'))) {
                                // if the code contains 'console.'
                                $result = preg_match('/' . 'console\.' . '/is', $code);
                                if ($result) {
                                    $useConsole = true;
                                }
                            }
                        }
                    }
                    $matchedTextToRender = "";
                    // Render the whole
                    if ($this->attributes["renderingmode"] != "onlyresult") {

                        // Replace babel by javascript because babel highlight does not exist in the dokuwiki and babel is only javascript ES2015
                        $matchedTextToRender = preg_replace('/<code[\s]+babel/', '<code javascript', $match);

                        // Delete a display="none" block
                        $matchedTextToRender = preg_replace('/<code([a-z\s]*)\[([a-z\s]*)display="none"([a-z\s]*)\]>(.*)<\/code>/msiU', '', $matchedTextToRender);

                    }
                    return array($state, $matchedTextToRender, $codes, $useConsole);

                } else {
                    throw new Exception("There was a match of the pattern but not when parsing");
                }


            case DOKU_LEXER_UNMATCHED :

                // Cache the values
                return array($state, $match);

            case DOKU_LEXER_EXIT:

                // Cache the values
                return array($state);

        }

    }

    /**
     * Render the output
     * @param string $mode
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return bool - rendered correctly (not used)
     *
     * The rendering process
     * @see DokuWiki_Syntax_Plugin::render()
     *
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // The $data variable comes from the handle() function
        //
        // $mode = 'xhtml' means that we output html
        // There is other mode such as metadata where you can output data for the headers (Not 100% sure)
        if ($mode == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            $state = $data[0];
            switch ($state) {

                case DOKU_LEXER_ENTER :

                    // The extracted data are the attribute of the webcode tag
                    // We put in a class variable so that we can use in the last step (DOKU_LEXER_EXIT)
                    $this->attributes = $data[1];

                    // Reinit the codes to make sure that the code does not leak into another webcode
                    $this->useConsole = false;
                    $this->codes = array();
                    break;

                case DOKU_LEXER_MATCHED :

                    // The extracted data are the codes for this step
                    // We put them in a class variable so that we can use them in the last step (DOKU_LEXER_EXIT)
                    $code = $data[2];
                    $codeType = key($code);
                    $this->codes[$codeType] = $this->codes[$codeType] . $code[$codeType];

                    // if not true, see if it's true
                    if (!$this->useConsole) {
                        $this->useConsole = $data[3];
                    }

                    // Render
                    $textToRender = $data[1];
                    if (!empty($textToRender)) {
                        $instructions = p_get_instructions($textToRender);
                        $xhtmlWebCode = self::p_render('xhtml', $instructions, $renderer->info);
                        $renderer->doc .= $xhtmlWebCode;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :

                    // Render and escape
                    $renderer->doc .= $renderer->_xmlEntities($data[1]);
                    break;

                case DOKU_LEXER_EXIT :
                    // Create the real output of webcode
                    if (sizeof($this->codes) == 0) {
                        return false;
                    }
                    // Dokuwiki Code ?
                    if (array_key_exists('dw', $this->codes)) {
                        $instructions = p_get_instructions($this->codes['dw']);
                        $renderer->doc .= self::p_render('xhtml', $instructions, $renderer->info);
                    } else {

                        // Js, Html, Css
                        $htmlContent = '<html><head>';
                        $htmlContent .= '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
                        $htmlContent .= '<title>Made by Webcode</title>';
                        $htmlContent .= '<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css">';


                        // External Resources such as css stylesheet or js
                        $externalResources = array();
                        if (array_key_exists(self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY, $this->attributes)) {
                            $externalResources = explode(",", $this->attributes[self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY]);
                        }

                        // Babel Preprocessor, if babel is used, add it to the external resources
                        if (array_key_exists('babel', $this->codes)) {
                            $babelMin = "https://unpkg.com/babel-standalone@6/babel.min.js";
                            // a load of babel invoke it (be sure to not have it twice
                            if (!(array_key_exists($babelMin, $externalResources))) {
                                $externalResources[] = $babelMin;
                            }
                        }

                        // Add the external resources
                        foreach ($externalResources as $externalResource) {
                            $pathInfo = pathinfo($externalResource);
                            $fileExtension = $pathInfo['extension'];
                            switch ($fileExtension) {
                                case 'css':
                                    $htmlContent .= '<link rel="stylesheet" type="text/css" href="' . $externalResource . '">';
                                    break;
                                case 'js':
                                    $htmlContent .= '<script type="text/javascript" src="' . $externalResource . '"/>';
                                    break;
                            }
                        }


                        // WebConsole style sheet
                        if ($this->useConsole) {
                            $htmlContent .= '<link rel="stylesheet" type="text/css" href="' . DOKU_URL . 'lib/plugins/webcode/webCodeConsole.css?ver=' . self::WEB_CONSOLE_CSS_VERSION . '"/>';
                        }

                        if (array_key_exists('css', $this->codes)) {
                            $htmlContent .= '<!-- The CSS code -->';
                            $htmlContent .= '<style>' . $this->codes['css'] . '</style>';
                        };
                        $htmlContent .= '</head><body style="margin:10px">';
                        if (array_key_exists('html', $this->codes)) {
                            $htmlContent .= '<!-- The HTML code -->';
                            $htmlContent .= $this->codes['html'];
                        }
                        // The javascript console area is based at the end of the HTML document
                        if ($this->useConsole) {
                            $htmlContent .= '<!-- WebCode Console -->';
                            $htmlContent .= '<div><p class=\'webConsoleTitle\'>Console Output:</p>';
                            $htmlContent .= '<div id=\'webCodeConsole\'/>';
                            $htmlContent .= '<script type=\'text/javascript\' src=\'' . DOKU_URL . 'lib/plugins/webcode/webCodeConsole.js?ver=' . self::WEB_CONSOLE_JS_VERSION . '\'/>';
                            $htmlContent .= '</div>';
                        }
                        // The javascript comes at the end because it may want to be applied on previous HTML element
                        // as the page load in the IO order, javascript must be placed at the end
                        if (array_key_exists('javascript', $this->codes)) {
                            $htmlContent .= '<!-- The Javascript code -->';
                            $htmlContent .= '<script type="text/javascript">' . $this->codes['javascript'] . '</script>';
                        }
                        if (array_key_exists('babel', $this->codes)) {
                            $htmlContent .= '<!-- The Babel code -->';
                            $htmlContent .= '<script type="text/babel">' . $this->codes['babel'] . '</script>';
                        }
                        $htmlContent .= '</body></html>';

                        // Here the magic from the plugin happens
                        // We add the Iframe and the JsFiddleButton
                        $iFrameHtml = '<iframe ';

                        // We add the name HTML attribute
                        $name = "WebCode iFrame";
                        if (array_key_exists('name', $this->attributes)) {
                            $name .= ' ' . $this->attributes['name'];
                        }
                        $iFrameHtml .= ' name="' . $name . '" ';

                        // The class to be able to select them
                        $iFrameHtml .= ' class="webCode" ';

                        // We add the others HTML attributes
                        $iFrameHtmlAttributes = array('width', 'height', 'frameborder', 'scrolling');
                        foreach ($this->attributes as $attribute => $value) {
                            if (in_array($attribute, $iFrameHtmlAttributes)) {
                                $iFrameHtml .= ' ' . $attribute . '=' . $value;
                            }
                        }
                        $iFrameHtml .= ' srcdoc="' . htmlentities($htmlContent) . '" ></iframe>';//

                        // Credits bar
                        $bar = '<div class="webcode-bar">';
                        $bar .= '<div class="webcode-bar-item"><a href="https://combostrap.com/webcode">' . $this->getLang('RenderedBy') . '</a></div>';
                        $bar .= '<div class="webcode-bar-item">'.$this->addJsFiddleButton($this->codes, $this->attributes).'</div>';
                        $bar .= '</div>';
                        $renderer->doc .= '<div class="webcode">' . $iFrameHtml . $bar . '</div>';
                    }

                    break;
            }

            return true;
        }
        return false;
    }

    /**
     * @param array $codes the array containing the codes
     * @param array $attributes the attributes of a call (for now the externalResources)
     * @return string the HTML form code
     *
     * Specification, see http://doc.jsfiddle.net/api/post.html
     */
    public function addJsFiddleButton($codes, $attributes)
    {

        $postURL = "https://jsfiddle.net/api/post/library/pure/"; //No Framework

        $externalResources = array();
        if (array_key_exists(self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY, $attributes)) {
            $externalResources = explode(",", $attributes[self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY]);
        }


        if ($this->useConsole) {
            // If their is a console.log function, add the Firebug Lite support of JsFiddle
            // Seems to work only with the Edge version of jQuery
            // $postURL .= "edge/dependencies/Lite/";
            // The firebug logging is not working anymore because of 404
            // Adding them here
            $externalResources[] = 'The firebug resources for the console.log features';
            $externalResources[] = DOKU_URL . 'lib/plugins/webcode/vendor/firebug-lite.css';
            $externalResources[] = DOKU_URL . 'lib/plugins/webcode/vendor/firebug-lite-1.2.js';
        }

        // The below code is to prevent this JsFiddle bug: https://github.com/jsfiddle/jsfiddle-issues/issues/726
        // The order of the resources is not guaranteed
        // We pass then the resources only if their is one resources
        // Otherwise we pass them as a script element in the HTML.
        if (count($externalResources) <= 1) {
            $externalResourcesInput = '<input type="hidden" name="resources" value="' . implode(",", $externalResources) . '">';
        } else {
            $codes['html'] .= "\n\n\n\n\n<!-- The resources -->\n";
            $codes['html'] .= "<!-- They have been added here because their order is not guarantee through the API. -->\n";
            $codes['html'] .= "<!-- See: https://github.com/jsfiddle/jsfiddle-issues/issues/726 -->\n";
            foreach ($externalResources as $externalResource) {
                if ($externalResource != "") {
                    $extension = pathinfo($externalResource)['extension'];
                    switch ($extension) {
                        case "css":
                            $codes['html'] .= "<link href=\"" . $externalResource . "\" rel=\"stylesheet\">\n";
                            break;
                        case "js":
                            $codes['html'] .= "<script src=\"" . $externalResource . "\"></script>\n";
                            break;
                        default:
                            $codes['html'] .= "<!-- " . $externalResource . " -->\n";
                    }
                }
            }
        }

        $jsCode = $codes['javascript'];
        $jsPanel = 0; // language for the js specific panel (0 = JavaScript)
        if (array_key_exists('babel', $this->codes)) {
            $jsCode = $codes['babel'];
            $jsPanel = 3; // 3 = Babel
        }

        // Title and description
        global $ID;
        $title = $attributes['name'];
        $pageTitle = tpl_pagetitle($ID, true);
        if (!$title) {

            $title = "Code from " . $pageTitle;
        }
        $description = "Code from the page '" . $pageTitle . "' \n" . wl($ID, $absolute = true);
        return '<form  method="post" action="' . $postURL . '" target="_blank">' .
        '<input type="hidden" name="title" value="' . htmlentities($title) . '">' .
        '<input type="hidden" name="description" value="' . htmlentities($description) . '">' .
        '<input type="hidden" name="css" value="' . htmlentities($codes['css']) . '">' .
        '<input type="hidden" name="html" value="' . htmlentities("<!-- The HTML -->" . $codes['html']) . '">' .
        '<input type="hidden" name="js" value="' . htmlentities($jsCode) . '">' .
        '<input type="hidden" name="panel_js" value="' . htmlentities($jsPanel) . '">' .
        '<input type="hidden" name="wrap" value="b">' .  //javascript no wrap in body
        $externalResourcesInput .
        '<button>' . $this->getLang('JsFiddleButtonContent') . '</button>' .
        '</form>';

    }

    /**
     * @param $codes the array containing the codes
     * @param $attributes the attributes of a call (for now the externalResources)
     * @return string the HTML form code
     */
    public function addCodePenButton($codes, $attributes)
    {
        // TODO
        // http://blog.codepen.io/documentation/api/prefill/
    }


    /**
     * @return string the mode (the name of this plugin for the lexer)
     */
    public function getPluginMode()
    {
        $pluginName = $this->getPluginName();
        $pluginComponent = $this->getPluginComponent();
        return 'plugin_' . $pluginName . '_' . $pluginComponent;
    }


}
