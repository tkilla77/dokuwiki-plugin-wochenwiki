<?php

use dokuwiki\Parsing\Handler;

/**
 * Wochenwiki: <woche> inside ++++ ... ++++ blocks of the folded plugin
 *
 * Folded blocks don't allow real DokuWiki headers. Like the folded plugin's own
 * header component, this renders a plain HTML header instead (no TOC entry,
 * no section editing).
 *
 * @author Tom Hofmann <tom@scheidweg.net>
 */
class syntax_plugin_wochenwiki_folded extends syntax_plugin_wochenwiki
{
    /** @inheritdoc */
    public function getType()
    {
        // folded blocks accept formatting; connectTo() limits this to folded blocks
        return 'formatting';
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        if ($mode !== 'plugin_folded_div') return;
        $this->Lexer->addSpecialPattern(self::TAG_PATTERN, $mode, 'plugin_wochenwiki_folded');
        $this->Lexer->addSpecialPattern(self::SCHOOLYEAR_PATTERN, $mode, 'plugin_wochenwiki_folded');
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Handler $handler)
    {
        if (str_starts_with($match, '~~')) return false;
        return $this->parseTag($match);
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        if (isset($data['error'])) return parent::render($format, $renderer, $data);
        if ($format !== 'xhtml') return false;

        $renderer->doc .= DOKU_LF . '<h' . $data['level'] . '>';
        $renderer->cdata($data['title']);
        $renderer->doc .= '</h' . $data['level'] . '>' . DOKU_LF;
        return true;
    }
}
