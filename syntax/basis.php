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

    const EXTERNAL_RESOURCES_ATTRIBUTE_DISPLAY = 'externalResources'; // In the action bar
    const EXTERNAL_RESOURCES_ATTRIBUTE_KEY = 'externalresources'; // In the code

    // Simple cache bursting implementation for the webCodeConsole.(js|css) file
    // They must be incremented manually when they changed
    const WEB_CONSOLE_CSS_VERSION = 1.1;
    const WEB_CONSOLE_JS_VERSION = 1.7;

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
                $attributes = array();
                $attributes['frameborder'] = 1;
                $attributes['width'] = '100%';

                // /i not case sensitive
                $attributePattern = "\s*(\w+)\s*=\s*\"?([^\"\s]+)\"?\\s*";
                $result = preg_match_all('/' . $attributePattern . '/i', $match, $matches);

                if ($result != 0) {
                    foreach ($matches[1] as $key => $nodeCodeContent) {
                        $attributes[strtolower($nodeCodeContent)] = $matches[2][$key];
                    }
                }

                // Cache the values
                return array($state, $attributes);

            case DOKU_LEXER_UNMATCHED :

                // We got the content between the webcode tag and its attributes
                // We parse it in order to extract the code in the codes array
                $codes = array();
                // Does the javascript contains a console statement
                $useConsole = false;

                // Regexp Pattern to parse the codes block
                $codePattern = "\<code\s*(\w+)\s*[\w\s\.]*\>(.+?)\<\/code\>";
                $result = preg_match_all('/' . $codePattern . '/is', $match, $matches, PREG_PATTERN_ORDER);
                if ($result != 0) {

                    // Loop through the block codes
                    foreach ($matches[1] as $key => $nodeCodeContent) {

                        // Get the code (The content between the code nodes)
                        $code = $matches[2][$key];

                        // The attributes of the code element
                        // Example:<code javascript type="text/babel">
                        $firstSpace = strpos($nodeCodeContent, " ");
                        // If there is a space, there is may be attributes
                        if ($firstSpace) {
                            $codeName = substr($nodeCodeContent, 0, $firstSpace);
                        } else {
                            // There is no attributes, this is the code name
                            $codeName = $nodeCodeContent;
                        }

                        // String are in lowercase
                        $lowerCodeName = strtolower($codeName);

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
                            if (in_array($lowerCodeName, array('babel','javascript', 'html', 'xml'))) {
                                // if the code contains 'console.'
                                $result = preg_match('/' . 'console\.' . '/is', $code);
                                if ($result) {
                                    $useConsole = true;
                                }
                            }
                        }
                    }
                }

                // Render the whole
                // Replace babel by javascript because babel highlight does not exist in the dokuwiki and babel is only javascript ES2015
                $matchedTextToRender = str_replace('babel', 'javascript', $match);
                $instructions = p_get_instructions($matchedTextToRender);
                $xhtmlWebCode = p_render('xhtml', $instructions, $info);

                // Cache the values
                return array($state, $xhtmlWebCode, $codes, $useConsole);

            case DOKU_LEXER_EXIT:

                // Cache the values
                return array($state);

        }

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

            $state = $data[0];
            switch ($state) {

                case DOKU_LEXER_ENTER :

                    // The extracted data are the attribute of the webcode tag
                    // We put in a class variable so that we can use in the last step (DOKU_LEXER_EXIT)
                    $this->attributes = $data[1];
                    break;

                case DOKU_LEXER_UNMATCHED :

                    // The extracted data are the codes for this step
                    // We put them in a class variable so that we can use them in the last step (DOKU_LEXER_EXIT)
                    $this->codes = $data[2];
                    $this->useConsole = $data[3];
                    // Add the wiki output between the two webcode tag
                    $renderer->doc .= $data[1];
                    break;

                case DOKU_LEXER_EXIT :


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
                        if (!(array_key_exists($babelMin,$externalResources))) {
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
                                $htmlContent .= '<script type="text/javascript" src="' . $externalResource . '"></script>';
                                break;
                        }
                    }


                    // WebConsole style sheet
                    if ($this->useConsole) {
                        $htmlContent .= '<link rel="stylesheet" type="text/css" href="' . DOKU_URL . 'lib/plugins/webcode/webCodeConsole.css?ver='.self::WEB_CONSOLE_CSS_VERSION.'"></link>';
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
                        $htmlContent .= '<div id=\'webCodeConsole\'></div>';
                        $htmlContent .= '<script type=\'text/javascript\' src=\'' . DOKU_URL . 'lib/plugins/webcode/webCodeConsole.js?ver='.self::WEB_CONSOLE_JS_VERSION.'\'></script>';
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

                    // Add the JsFiddle button
                    $renderer->doc .= '<div>' . $this->addJsFiddleButton($this->codes, $this->attributes) . $iFrameHtml . '</div>';


                    break;
            }

            return true;
        }
        return false;
    }

    /**
     * @param $codes the array containing the codes
     * @param $attributes the attributes of a call (for now the externalResources)
     * @return string the HTML form code
     *
     * Specification, see http://doc.jsfiddle.net/api/post.html
     */
    public function addJsFiddleButton($codes, $attributes)
    {

        $postURL = "https://jsfiddle.net/api/post/library/pure/"; //No Framework
        if (array_key_exists('javascript', $this->codes)) {
            $postURL = "https://jsfiddle.net/api/post/jQuery/";
            if ($this->useConsole) {
                // If their is a console.log function, add the Firebug Lite support of JsFiddle
                // Seems to work only with the Edge version of jQuery
                $postURL .= "edge/dependencies/Lite/";
            } else {
                $postURL .= '2.1.3/'; // The end backslash is required in the JSFiddle URL scheme.
            }
        }

        $externalResourcesInput = '';
        if (array_key_exists(self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY, $attributes)) {
            // The below code is to prevent this JsFiddle bug: https://github.com/jsfiddle/jsfiddle-issues/issues/726
            // The order of the resources is not guaranteed
            // We pass then the resources only if their is one resources
            // Otherwise we pass them as a script element in the HTML.
            $externalResources = explode(",",$attributes[self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY]);
            if (count($externalResources)<=1) {
                $externalResourcesInput = '<input type="hidden" name="resources" value="' . implode(",", $externalResources) . '">';
            } else {
                $codes['html'] .=  "\n\n<!-- The resources have been added here because their order is not guarantee through the API. -->\n";
                $codes['html'] .=  "<!-- See: https://github.com/jsfiddle/jsfiddle-issues/issues/726 -->\n";
                foreach ($externalResources as $externalResource) {
                    $codes['html'] .=  "<script src=\"".$externalResource."\"></script>\n";
                }
            }
        }

        $jsCode = $codes['javascript'];
        $jsPanel = 0; // language for the js specific panel (0 = JavaScript)
        if (array_key_exists('babel',$codes)) {
            $jsCode = $codes['babel'];
            $jsPanel = 3; // 3 = Babel
        }

        // Title and description
        global $ID;
        $title=$attributes['name'];
        $pageTitle = tpl_pagetitle($ID, true);
        if (!$title) {

            $title="Code from ". $pageTitle;
        }
        $description="Code from the page '". $pageTitle ."' \n".wl($ID,$absolute=true);

        $jsFiddleButtonHtmlCode =
            '<div class="webcodeButton">' .
            '<form method="post" action="' . $postURL . '" target="_blank">' .
            '<input type="hidden" name="title" value="'. htmlentities($title).'">' .
            '<input type="hidden" name="description" value="'. htmlentities($description).'">' .
            '<input type="hidden" name="css" value="' . htmlentities($codes['css']) . '">' .
            '<input type="hidden" name="html" value="' . htmlentities($codes['html']) . '">' .
            '<input type="hidden" name="js" value="' . htmlentities($jsCode) . '">' .
            '<input type="hidden" name="panel_js" value="' . htmlentities($jsPanel) . '">' .
            '<input type="hidden" name="wrap" value="b">' .  //javascript no wrap in body
            $externalResourcesInput .
            '<button class="btn btn-link">' . $this->getLang('JsFiddleButtonContent') . '</button>' .
            '</form>' .
            '</div>';

        return $jsFiddleButtonHtmlCode;

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

}
