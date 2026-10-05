<?php

namespace dokuwiki\plugin\wochenwiki\test;

use DokuWikiTest;

/**
 * <woche> inside ++++ ... ++++ blocks of the folded plugin (installed via requirements.txt)
 *
 * @group plugin_wochenwiki
 * @group plugins
 */
class FoldedTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['wochenwiki', 'folded'];

    /**
     * Render wiki text to XHTML
     */
    protected function render(string $text): string
    {
        $info = [];
        return p_render('xhtml', p_get_instructions($text), $info);
    }

    public function testWeekInsideFoldedBlock(): void
    {
        $html = $this->render("++++ Herbstquartal 2026 |\n<woche 40/2026>\n  * Lektion 1\n++++\n");
        $this->assertStringContainsString('<h4>Woche 40 (2026-09-28)</h4>', $html);
        $this->assertStringNotContainsString('&lt;woche', $html);
        $this->assertStringContainsString('Lektion 1', $html);
    }

    public function testSchoolYearInsideFoldedBlock(): void
    {
        $html = $this->render("~~SCHULJAHR:2026~~\n\n++++ Frühlingsquartal |\n<woche 12>\n  * Lektion 1\n++++\n");
        $this->assertStringContainsString('<h4>Woche 12 (2027-03-22)</h4>', $html);
    }

    public function testErrorInsideFoldedBlock(): void
    {
        $html = $this->render("++++ Quartal |\n<woche 40>\n++++\n");
        $this->assertStringContainsString('class="error"', $html);
    }

    public function testOutsideStillRealHeader(): void
    {
        $html = $this->render("<woche 40/2026>\n\n++++ Quartal |\n<woche 39/2026>\n++++\n");
        $this->assertMatchesRegularExpression('/<h4 id="[^"]+">Woche 40 \(2026-09-28\)<\/h4>/', $html);
        $this->assertStringContainsString('<h4>Woche 39 (2026-09-21)</h4>', $html);
    }
}
