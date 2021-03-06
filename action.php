<?php
/**
 * Action Component for the Webcode Plugin
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_webcode extends DokuWiki_Action_Plugin {

    /**
     * register the event handlers
     *
     * @param Doku_Event_Handler $controller
     * @author Nicolas GERARD
     */
    function register(Doku_Event_Handler $controller){
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array ());
    }

    function handle_toolbar(&$event, $param) {
        $webCodeShortcutKey = $this->getConf('WebCodeShortCutKey');

        $event->data[] = array(
            'type'   => 'format',
            'title'  => $this->getLang('WebCodeButtonTitle').' ('.$this->getLang('AccessKey').': '.$webCodeShortcutKey.')',
            'icon'   => '../../plugins/webcode/images/webcode.png',
            'open'   => '<webcode name="Default" frameborder=0 width=100% scrolling=yes '.syntax_plugin_webcode_basis::EXTERNAL_RESOURCES_ATTRIBUTE_DISPLAY.'="," renderingMode=story >\n',
            'close'  => '\n</webcode>\n',
            'key'    => $webCodeShortcutKey
        );


    }

}

