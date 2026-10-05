<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * Wochenwiki: turns <woche 40/2026> into a regular DokuWiki header
 * "Woche 40 (2026-09-28)" (ISO week number + date of its Monday).
 *
 * Accepted forms:
 *   <woche 40/2026>          week/year
 *   <woche 2026-W40>         ISO notation
 *   <woche 2026-09-30>       any date, normalised to the Monday of its week
 *   <woche 40/2026 | Text>   optional suffix: "Woche 40 (2026-09-28) – Text"
 *   <woche 40>               year taken from ~~SCHULJAHR:2026~~ on the same page:
 *                            weeks after the 'boundary' week fall into 2026, the others into 2027
 *
 * @author Tom Hofmann <tom@scheidweg.net>
 */
class syntax_plugin_wochenwiki extends SyntaxPlugin
{
    // Note: keep compatible with DokuWiki before "Mort" (Doku_Handler type hint, PHP 7.4 syntax).
    // The automatic code style PR proposes changes that break this; don't merge those parts.

    /** @var int|null start year of the school year of the page being parsed, see action.php */
    public static $schoolYear;

    /** @inheritdoc */
    public function getType()
    {
        // same type as native headers, so <woche> is allowed wherever headers are
        return 'baseonly';
    }

    /** @inheritdoc */
    public function getPType()
    {
        return 'block';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 49;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(self::TAG_PATTERN, $mode, 'plugin_wochenwiki');
        $this->Lexer->addSpecialPattern(self::SCHOOLYEAR_PATTERN, $mode, 'plugin_wochenwiki');
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        // ~~SCHULJAHR:2026~~ was already read by action.php and renders nothing
        if (substr($match, 0, 2) === '~~') return false;

        $data = $this->parseTag($match);
        if (isset($data['error'])) return $data;

        // Let the core create the header exactly like a hand-written one:
        // TOC entry, anchor, section edit button and the surrounding section.
        $equals = str_repeat('=', 7 - $data['level']);
        $header = "$equals {$data['title']} $equals";
        if (class_exists('dokuwiki\\Parsing\\ModeRegistry')) {
            // newer DokuWiki: header() is a deprecated wrapper around the mode object
            $handler->handleToken('header', $header, $state, $pos);
        } else {
            $handler->header($header, $state, $pos);
        }

        return false; // no plugin instruction needed
    }

    /**
     * Turn a <woche ...> tag into the header title and level
     *
     * @param string $match the complete tag
     * @return array ['title' => ..., 'level' => ...] or ['error' => spec, 'msg' => lang key]
     */
    protected function parseTag($match)
    {
        $inner = trim(substr(trim($match), strlen('<woche'), -1));
        $parts = array_map('trim', explode('|', $inner, 2));
        $spec = $parts[0];
        $note = $parts[1] ?? '';

        if (preg_match('/^\d{1,2}$/', $spec)) {
            if (self::$schoolYear === null) {
                return ['error' => $spec, 'msg' => 'noyear'];
            }
            $spec .= '/' . self::schoolYearToYear((int)$spec, self::$schoolYear, (int)$this->getConf('boundary'));
        }

        $monday = self::parseWeek($spec);
        if ($monday === null) {
            return ['error' => $spec, 'msg' => 'invalid'];
        }

        $title = sprintf('%s %d (%s)', $this->getConf('label'), (int)$monday->format('W'), $monday->format('Y-m-d'));
        if ($note !== '') {
            $title .= ' – ' . $note;
        }

        return ['title' => $title, 'level' => max(1, min(5, (int)$this->getConf('level')))];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        // only reached for invalid specs
        if ($format !== 'xhtml') return false;
        $renderer->doc .= '<div class="error">' . hsc($this->getLang($data['msg']) . ': <woche ' . $data['error'] . '>')
            . '</div>';
        return true;
    }

    /** matches <woche ...> on a line of its own */
    public const TAG_PATTERN = '[ \t]*<[Ww]oche\b[^>\n]*>[ \t]*(?=\n)';

    /** matches ~~SCHULJAHR:2026~~ and ~~SCHULJAHR:2026/27~~ */
    public const SCHOOLYEAR_PATTERN = '~~SCHULJAHR:\s*\d{4}(?:/\d{2,4})?\s*~~';

    /**
     * Remember the school year declared anywhere in the given wiki text (null if none)
     *
     * @param string $text raw wiki text about to be parsed
     */
    public static function readSchoolYear($text)
    {
        self::$schoolYear = preg_match('#' . self::SCHOOLYEAR_PATTERN . '#', $text, $m)
            ? (int)substr(trim(substr($m[0], strlen('~~SCHULJAHR:'))), 0, 4)
            : null;
    }

    /**
     * Calendar year of a week within a school year
     *
     * @param int $week ISO week number
     * @param int $schoolYear year the school year starts in
     * @param int $boundary last week belonging to the previous school year (e.g. 32)
     * @return int
     */
    public static function schoolYearToYear($week, $schoolYear, $boundary)
    {
        return $week > $boundary ? $schoolYear : $schoolYear + 1;
    }

    /**
     * Parse a week specification and return the Monday of that ISO week.
     *
     * @param string $spec e.g. "40/2026", "2026-W40" or "2026-09-30"
     * @return DateTimeImmutable|null null if the spec is not valid
     */
    public static function parseWeek($spec)
    {
        $utc = new DateTimeZone('UTC');

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $spec, $m)) {
            if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
            $date = (new DateTimeImmutable('now', $utc))->setDate((int)$m[1], (int)$m[2], (int)$m[3]);
            $year = (int)$date->format('o');
            $week = (int)$date->format('W');
        } elseif (preg_match('/^(\d{1,2})\s*[\/.]\s*(\d{4})$/', $spec, $m)) {
            $week = (int)$m[1];
            $year = (int)$m[2];
        } elseif (preg_match('/^(\d{4})-?W(\d{1,2})$/i', $spec, $m)) {
            $year = (int)$m[1];
            $week = (int)$m[2];
        } else {
            return null;
        }

        if ($week < 1 || $week > 53) return null;
        $monday = (new DateTimeImmutable('now', $utc))->setISODate($year, $week, 1)->setTime(0, 0);
        // reject week 53 in years that only have 52 weeks
        if ((int)$monday->format('W') !== $week) return null;
        return $monday;
    }
}
