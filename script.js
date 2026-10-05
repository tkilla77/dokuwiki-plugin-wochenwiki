/**
 * Wochenwiki: current week highlighting and the "new week" toolbar button
 *
 * @author Tom Hofmann <tom@scheidweg.net>
 */

/**
 * Monday (local midnight) of the ISO week containing the given date
 */
function wochenwiki_monday(date) {
    var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    d.setDate(d.getDate() - (d.getDay() + 6) % 7);
    return d;
}

/**
 * ISO week number and ISO year of the given date
 */
function wochenwiki_isoweek(date) {
    var thursday = wochenwiki_monday(date);
    thursday.setDate(thursday.getDate() + 3);
    var jan1 = new Date(thursday.getFullYear(), 0, 1);
    return {
        week: 1 + Math.floor(Math.round((thursday - jan1) / 86400000) / 7),
        year: thursday.getFullYear()
    };
}

/**
 * Format a date as YYYY-MM-DD (local time)
 */
function wochenwiki_ymd(date) {
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
}

/**
 * Parse a YYYY-MM-DD string as local date
 */
function wochenwiki_parse(ymd) {
    var p = ymd.split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]);
}

/**
 * Monday of the given ISO week
 */
function wochenwiki_weekmonday(week, year) {
    var monday = wochenwiki_monday(new Date(year, 0, 4));
    monday.setDate(monday.getDate() + 7 * (week - 1));
    return monday;
}

/**
 * Monday of the week given in a <woche ...> tag (same forms as syntax.php), or null
 *
 * @param {string} spec e.g. "40/2026", "2026-W40", "2026-09-30" or "40"
 * @param {number|null} schoolYear start year from ~~SCHULJAHR~~, needed for "40"
 */
function wochenwiki_parsespec(spec, schoolYear) {
    var boundary = (JSINFO.plugin_wochenwiki || {}).boundary || 32;
    var m;
    if ((m = spec.match(/^(\d{4}-\d{1,2}-\d{1,2})$/))) return wochenwiki_monday(wochenwiki_parse(m[1]));
    if ((m = spec.match(/^(\d{1,2})\s*[\/.]\s*(\d{4})$/))) return wochenwiki_weekmonday(+m[1], +m[2]);
    if ((m = spec.match(/^(\d{4})-?W(\d{1,2})$/i))) return wochenwiki_weekmonday(+m[2], +m[1]);
    if ((m = spec.match(/^(\d{1,2})$/)) && schoolYear) {
        return wochenwiki_weekmonday(+m[1], +m[1] > boundary ? schoolYear : schoolYear + 1);
    }
    return null;
}

/**
 * Toolbar button: insert the current week, or if it is already on the page,
 * the week after the newest one found. Pages with ~~SCHULJAHR~~ get <woche N>
 * without year, so they can be reused next year by changing the directive.
 */
function tb_wochenwiki(btn, props, edid) {
    var text = jQuery('#' + edid).val();
    var label = (JSINFO.plugin_wochenwiki || {}).label || 'Woche';
    var mondays = [];
    var m, re;

    m = text.match(/~~SCHULJAHR:\s*(\d{4})/);
    var schoolYear = m ? +m[1] : null;

    // hand-written headers: "Woche 40 (2026-09-28)"
    re = new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ' \\d+ \\((\\d{4}-\\d{2}-\\d{2})\\)', 'g');
    while ((m = re.exec(text))) mondays.push(wochenwiki_monday(wochenwiki_parse(m[1])));

    // plugin syntax: <woche 40/2026>, <woche 40> etc.
    re = /<woche\s+([^>|\n]*)[^>\n]*>/gi;
    while ((m = re.exec(text))) {
        var monday = wochenwiki_parsespec(m[1].trim(), schoolYear);
        if (monday) mondays.push(monday);
    }

    var current = wochenwiki_monday(new Date());
    var target = current;
    var known = mondays.map(wochenwiki_ymd);
    if (known.indexOf(wochenwiki_ymd(current)) !== -1) {
        var newest = mondays.reduce(function (a, b) { return a > b ? a : b; });
        target = new Date(newest);
        target.setDate(target.getDate() + 7);
    }

    var iso = wochenwiki_isoweek(target);
    var spec = iso.week + '/' + iso.year;
    if (schoolYear && wochenwiki_ymd(wochenwiki_parsespec('' + iso.week, schoolYear)) === wochenwiki_ymd(target)) {
        spec = '' + iso.week; // the short form resolves to the same week
    }
    insertAtCarret(edid, '<woche ' + spec + '>\n  * \n\n');
    return false;
}

/**
 * Highlight the header and section of the current week
 */
jQuery(function () {
    var label = (JSINFO.plugin_wochenwiki || {}).label || 'Woche';
    var today = wochenwiki_ymd(wochenwiki_monday(new Date()));

    jQuery('div.page').find('h1, h2, h3, h4, h5').each(function () {
        var $h = jQuery(this);
        var m = $h.text().match(/\((\d{4}-\d{2}-\d{2})\)/);
        if (!m || $h.text().indexOf(label) !== 0) return;
        if (wochenwiki_ymd(wochenwiki_monday(wochenwiki_parse(m[1]))) !== today) return;

        var level = this.tagName.substr(1);
        $h.addClass('wochenwiki-current')
            .attr('title', LANG.plugins.wochenwiki.current);
        var $section = $h.next('div.level' + level);
        if (!$section.length) {
            // headers inside folded blocks have no section div: wrap the week's content
            $section = $h.nextUntil('h1, h2, h3, h4, h5, h6').wrapAll('<div></div>').parent();
        }
        $section.addClass('wochenwiki-current');
    });
});
