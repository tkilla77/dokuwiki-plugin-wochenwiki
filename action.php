<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * Wochenwiki: editor toolbar button and JS configuration
 *
 * @author Tom Hofmann <tom@scheidweg.net>
 */
class action_plugin_wochenwiki extends ActionPlugin
{
    /** @inheritdoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'addToolbarButton');
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'addJsInfo');
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'readSchoolYear');
    }

    /**
     * Button that inserts the next week not yet on the page (see tb_wochenwiki in script.js)
     */
    public function addToolbarButton(Event $event)
    {
        $event->data[] = [
            'type' => 'wochenwiki',
            'title' => $this->getLang('toolbar'),
            'icon' => '../../plugins/wochenwiki/images/toolbar.svg',
        ];
    }

    /**
     * The highlighting script needs to know how week headers start
     */
    public function addJsInfo(Event $event)
    {
        global $JSINFO;
        $JSINFO['plugin_wochenwiki'] = [
            'label' => $this->getConf('label'),
            'boundary' => (int)$this->getConf('boundary'),
        ];
    }

    /**
     * Year-less <woche 40> needs ~~SCHULJAHR~~, which may stand anywhere on the page,
     * so look it up before parsing starts (the text itself is left untouched).
     */
    public function readSchoolYear(Event $event)
    {
        syntax_plugin_wochenwiki::readSchoolYear($event->data);
    }
}
