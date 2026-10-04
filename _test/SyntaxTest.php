<?php

namespace dokuwiki\plugin\wochenwiki\test;

use DokuWikiTest;

/**
 * Rendering tests for the <woche> syntax
 *
 * @group plugin_wochenwiki
 * @group plugins
 */
class SyntaxTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['wochenwiki'];

    /**
     * Render wiki text to XHTML
     */
    protected function render(string $text): string
    {
        $info = [];
        return p_render('xhtml', p_get_instructions($text), $info);
    }

    public static function provideWeeks(): array
    {
        return [
            'week/year' => ['<woche 40/2026>', 'Woche 40 (2026-09-28)'],
            'iso' => ['<woche 2026-W40>', 'Woche 40 (2026-09-28)'],
            'date' => ['<woche 2026-10-01>', 'Woche 40 (2026-09-28)'],
            'year boundary' => ['<woche 2025-12-31>', 'Woche 1 (2025-12-29)'],
            'week 53' => ['<woche 53/2026>', 'Woche 53 (2026-12-28)'],
            'note' => ['<woche 40/2026 | Prüfung>', 'Woche 40 (2026-09-28) – Prüfung'],
            'school year autumn' => ["~~SCHULJAHR:2026~~\n<woche 40>", 'Woche 40 (2026-09-28)'],
            'school year spring' => ["~~SCHULJAHR:2026/27~~\n<woche 12>", 'Woche 12 (2027-03-22)'],
            'directive below' => ["<woche 12>\n\n~~SCHULJAHR:2026~~", 'Woche 12 (2027-03-22)'],
            'explicit year wins' => ["~~SCHULJAHR:2026~~\n<woche 12/2026>", 'Woche 12 (2026-03-16)'],
        ];
    }

    /**
     * @dataProvider provideWeeks
     */
    public function testHeader(string $text, string $title): void
    {
        $html = $this->render($text . "\n  * Lektion 1\n");
        $this->assertMatchesRegularExpression('/<h4 id="[^"]+">' . preg_quote($title, '/') . '<\/h4>/', $html);
        $this->assertStringNotContainsString('SCHULJAHR', $html);
        $this->assertStringNotContainsString('class="error"', $html);
    }

    public function testTocEntry(): void
    {
        global $conf;
        $conf['maxtoclevel'] = 5;
        $instructions = p_get_instructions("<woche 40/2026>\n\n<woche 41/2026>\n");
        $headers = array_values(array_filter($instructions, fn($i) => $i[0] === 'header'));
        $this->assertCount(2, $headers);
        $this->assertEquals('Woche 41 (2026-10-05)', $headers[1][1][0]);
    }

    public static function provideErrors(): array
    {
        return [
            'no school year' => ['<woche 40>'],
            'week 53 in 52-week year' => ['<woche 53/2025>'],
            'week 0' => ['<woche 0/2026>'],
            'bad date' => ['<woche 2026-02-30>'],
            'garbage' => ['<woche nächste>'],
        ];
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors(string $text): void
    {
        $html = $this->render($text . "\n");
        $this->assertStringContainsString('class="error"', $html);
        $this->assertStringNotContainsString('<h4', $html);
    }

    public function testSchoolYearDoesNotLeak(): void
    {
        $this->render("~~SCHULJAHR:2026~~\n<woche 40>\n");
        $html = $this->render("<woche 40>\n");
        $this->assertStringContainsString('class="error"', $html);
    }
}
