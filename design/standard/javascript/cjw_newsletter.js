/*
 * cjw_newsletter: the behavior of the newsletter templates, without inline code.
 *
 * Printed with {ezscript( array( 'cjw_newsletter.js' ) )} by every template that needs it; the file may be printed
 * more than once on a page and sets itself up once. It works in every administration design (admin, admin2, admin3,
 * admin4, admin4l) and needs no library: it uses only the global functions of the kernel's own scripts
 * (ezpopupmenu.js: ezpopmenu_showTopLevel, ezpopmenu_hideAll; contentstructuremenu.js: ezcst_*) where the old inline
 * code called them.
 *
 * Attributes:
 *   form[data-nl-confirm="Question?"]            asks before the form is sent
 *   [type=submit][data-nl-confirm-button="Q?"]   asks before this button sends its form
 *   [data-cb-confirm="Question?"]                asks before the click goes on (link or button)
 *   input[type=checkbox][data-nl-check-all]      checks or unchecks every named checkbox of its table
 *   [data-cjwnl-toggle-checkboxes="Name[]"]      inverts the checkboxes of that name in its form
 *                                                (data-cjwnl-form="<form name>" for another form)
 *   [data-cjwnl-toggle-multiple="<select id>"]   switches a select between single and multiple choice
 *   a[data-cjwnl-menu]                           opens a context menu of ezpopupmenu.js; JSON:
 *                                                {"menu":"ClassMenu","values":{...},"title":"...","disable":-1,"disableItem":-1}
 *   a.openclose[data-cjwnl-fold]                 folds or unfolds a node of the newsletter tree
 *   ul[data-cjwnl-tree]                          the newsletter tree; JSON: {"nodes":[...],"icons":[...],
 *                                                "openIcon":..,"closeIcon":..,"emptyIcon":..,"autoopen":".."}
 *   select[data-cjwnl-languages]                 the class menu of "Create here"; JSON {classID: [locales]}:
 *                                                languages a class cannot be created in are disabled in the
 *                                                select[data-cjwnl-language-select] of the same form
 *   [data-cjwnl-sort-highlight]                  the "Set" button of the sorting: highlighted when the sort
 *                                                field or order (#ezasi-sort-field, #ezasi-sort-order) changed
 *   section#nl-job[data-url][data-labels]        polls a background run and shows its progress
 *   textarea[data-nl-sms-counter="<id>"]         live length counter of an SMS text (data-nl-sms-hint, data-nl-sms-max)
 */
(function () {
    'use strict';
    if (window.cjwNewsletterScript) {
        return;
    }
    window.cjwNewsletterScript = true;

    function json(el, name, fallback) {
        try {
            return JSON.parse(el.getAttribute(name) || '');
        } catch (e) {
            return fallback;
        }
    }

    // The menu arguments the templates had as JavaScript: -1, 'item-id' or ['item-a', 'item-b'].
    function jsArg(value) {
        if (typeof value !== 'string') {
            return value;
        }
        var s = value.trim();
        if (/^-?\d+$/.test(s)) {
            return parseInt(s, 10);
        }
        try {
            return JSON.parse(s.replace(/'/g, '"'));
        } catch (e) {
            return s;
        }
    }

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function closest(el, selector) {
        while (el && el.nodeType === 1) {
            if (el.matches ? el.matches(selector) : el.msMatchesSelector(selector)) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }

    // Confirmations: a form with data-nl-confirm, a submit button with data-nl-confirm-button.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var submitter = event.submitter;
        var text = submitter && submitter.getAttribute && submitter.getAttribute('data-nl-confirm-button');
        if (!text && form.getAttribute) {
            text = form.getAttribute('data-nl-confirm');
        }
        if (text && !window.confirm(text)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        var el;

        el = closest(target, '[data-cb-confirm]');
        if (el) {
            if (!window.confirm(el.getAttribute('data-cb-confirm'))) {
                event.preventDefault();
            }
            return;
        }

        el = closest(target, '[data-cjwnl-toggle-checkboxes]');
        if (el) {
            event.preventDefault();
            var formName = el.getAttribute('data-cjwnl-form');
            var form = formName ? document.forms[formName] : closest(el, 'form');
            var name = el.getAttribute('data-cjwnl-toggle-checkboxes');
            if (form) {
                Array.prototype.forEach.call(form.elements, function (box) {
                    if (box.type === 'checkbox' && box.name === name && !box.disabled) {
                        box.checked = !box.checked;
                    }
                });
            }
            return;
        }

        el = closest(target, '[data-cjwnl-toggle-multiple]');
        if (el) {
            event.preventDefault();
            var select = document.getElementById(el.getAttribute('data-cjwnl-toggle-multiple'));
            if (select) {
                select.multiple = !select.multiple;
            }
            return;
        }

        el = closest(target, 'a[data-cjwnl-menu]');
        if (el && typeof window.ezpopmenu_showTopLevel === 'function') {
            var conf = json(el, 'data-cjwnl-menu', null);
            if (!conf || !conf.menu) {
                return;
            }
            event.preventDefault();
            // ezpopupmenu.js reads the position, the kind of the click and the element that opened the menu
            var proxy = {
                type: event.type, detail: event.detail, pageX: event.pageX, pageY: event.pageY,
                clientX: event.clientX, clientY: event.clientY, target: event.target, srcElement: event.target,
                currentTarget: el,
                preventDefault: function () { event.preventDefault(); },
                stopPropagation: function () { event.stopPropagation(); }
            };
            var values = conf.values || {};
            if (el.hasAttribute('data-cjwnl-menu-languages')) {
                values['%languages%'] = json(el, 'data-cjwnl-menu-languages', undefined);
            }
            window.ezpopmenu_showTopLevel(proxy, conf.menu, values, conf.title || '',
                jsArg('disable' in conf ? conf.disable : -1), jsArg('disableItem' in conf ? conf.disableItem : -1));
            return;
        }

        el = closest(target, 'a[data-cjwnl-fold]');
        if (el) {
            event.preventDefault();
            if (typeof window.ezpopmenu_hideAll === 'function') {
                window.ezpopmenu_hideAll();
            }
            if (typeof window.ezcst_onFoldClicked === 'function') {
                window.ezcst_onFoldClicked(el.parentNode);
            }
        }
    });

    // Select all: a checkbox in a table header checks every named checkbox of the table.
    document.addEventListener('change', function (event) {
        var box = event.target;
        if (box.matches && box.matches('input[data-nl-check-all]')) {
            var table = closest(box, 'table');
            if (table) {
                Array.prototype.forEach.call(table.querySelectorAll('input[type=checkbox][name]'), function (c) {
                    c.checked = box.checked;
                });
            }
            return;
        }
        if (box.matches && box.matches('select[data-cjwnl-languages]')) {
            updateLanguageSelector(box);
            return;
        }
        if (box.matches && box.matches('select[data-cjwnl-language-select]')) {
            checkLanguageSelector(box);
        }
    });

    // "Create here": the languages a class can be created in.
    function findLanguageSelect(classSelect) {
        return classSelect.form ? classSelect.form.querySelector('select[data-cjwnl-language-select]') : null;
    }

    function updateLanguageSelector(classSelect) {
        var languageSelect = findLanguageSelect(classSelect);
        if (!languageSelect) {
            return;
        }
        var languages = json(classSelect, 'data-cjwnl-languages', {})[classSelect.value] || [];
        var candidate = -1;
        Array.prototype.forEach.call(languageSelect.options, function (option, index) {
            var disabled = languages.indexOf(option.value) === -1;
            if (!disabled && candidate === -1) {
                candidate = index;
            }
            option.disabled = disabled;
            if (disabled) {
                option.style.color = '#888';
                if (option.text.substring(0, 1) !== '(') {
                    option.text = '(' + option.text + ')';
                }
            } else {
                option.style.color = '';
                if (option.text.substring(0, 1) === '(') {
                    option.text = option.text.substring(1, option.text.length - 1);
                }
            }
        });
        var selected = languageSelect.options[languageSelect.selectedIndex];
        if (selected && selected.disabled) {
            languageSelect.selectedIndex = candidate;
        }
        languageSelect.setAttribute('data-cjwnl-language-index', languageSelect.selectedIndex);
    }

    function checkLanguageSelector(languageSelect) {
        var selected = languageSelect.options[languageSelect.selectedIndex];
        if (selected && selected.disabled) {
            languageSelect.selectedIndex = parseInt(languageSelect.getAttribute('data-cjwnl-language-index') || '0', 10);
            return;
        }
        languageSelect.setAttribute('data-cjwnl-language-index', languageSelect.selectedIndex);
    }

    // The sorting of the sub items: the "Set" button is highlighted when field or order differ from the saved ones.
    function sortHighlight(button) {
        var field = document.getElementById('ezasi-sort-field');
        var order = document.getElementById('ezasi-sort-order');
        if (!field || !order) {
            return;
        }
        var initial = { field: field.value, order: order.value };
        function update() {
            var changed = field.value !== initial.field || order.value !== initial.order;
            button.classList.toggle('defaultbutton', changed);
            button.classList.toggle('button', !changed);
        }
        field.addEventListener('change', update);
        order.addEventListener('change', update);
    }

    // The newsletter tree (contentstructuremenu/1_newsletter_structure_menu.tpl).
    function tree(menu) {
        var conf = json(menu, 'data-cjwnl-tree', {});
        if (conf.icons && conf.icons.length && typeof window.ezjslib_preloadImageList === 'function') {
            window.ezjslib_preloadImageList(conf.icons);
        }
        if (typeof window.ezcst_setFoldUnfoldIcons === 'function' && typeof window.ezcst_initializeMenuState === 'function') {
            window.ezcst_setFoldUnfoldIcons(conf.openIcon, conf.closeIcon, conf.emptyIcon);
            window.ezcst_initializeMenuState(conf.nodes || [], menu.id, conf.autoopen || '');
        }
    }

    // The progress of a background run (parts/newsletter/job.tpl).
    function job(box) {
        var labels = json(box, 'data-labels', {});
        var classes = { starting: 'is-info', running: 'is-warn', done: 'is-ok', failed: 'is-bad', unknown: 'is-muted' };
        var keys = ['sends', 'items', 'sent', 'failed', 'finished', 'waiting', 'mailboxes', 'collected', 'parsed',
            'subscriptions', 'send_items', 'rows', 'users', 'invalid'];
        function poll() {
            fetch(box.getAttribute('data-url'), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var status = box.querySelector('#nl-job-status');
                    status.textContent = labels[data.status] || data.status;
                    status.className = 'nl-pill ' + (classes[data.status] || 'is-muted');
                    box.querySelector('#nl-job-log').textContent = (data.log || []).join('\n');
                    var result = data.result || {};
                    var parts = [];
                    keys.forEach(function (key) {
                        if (typeof result[key] === 'number') {
                            parts.push(key + ': ' + result[key]);
                        }
                    });
                    if (result.error) {
                        parts.push(result.error);
                    }
                    box.querySelector('#nl-job-result').textContent = parts.join(' · ');
                    if (data.status === 'starting' || data.status === 'running') {
                        window.setTimeout(poll, 2000);
                    }
                })
                .catch(function () { window.setTimeout(poll, 5000); });
        }
        poll();
    }

    // The live length counter of an SMS text: counts as CjwNewsletterSms::segments() does: GSM 03.38
    // (160 / 153 per part, extension characters twice) or UCS-2 (70 / 67).
    var gsmBasic = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    var gsmExtended = "^{}\\[~]|€\f";

    function smsCount(text) {
        var chars = Array.from(text), units = 0, gsm = true, i;
        for (i = 0; i < chars.length; i++) {
            if (gsmBasic.indexOf(chars[i]) !== -1) { units += 1; }
            else if (gsmExtended.indexOf(chars[i]) !== -1) { units += 2; }
            else { gsm = false; break; }
        }
        if (!gsm) { units = 0; for (i = 0; i < chars.length; i++) { units += chars[i].length; } }
        var single = gsm ? 160 : 70, part = gsm ? 153 : 67;
        var segments = units === 0 ? 0 : (units <= single ? 1 : Math.ceil(units / part));
        var per = segments <= 1 ? single : part;
        return { encoding: gsm ? 'GSM 7-bit' : 'Unicode', units: units, segments: segments, remaining: Math.max(segments, 1) * per - units };
    }

    function smsCounter(area) {
        var box = document.getElementById(area.getAttribute('data-nl-sms-counter'));
        if (!box) {
            return;
        }
        var hint = area.getAttribute('data-nl-sms-hint') || '';
        var max = parseInt(area.getAttribute('data-nl-sms-max') || '0', 10);
        function update() {
            var text = area.value.replace(/\r\n/g, '\n').trim();
            var c = smsCount(text + (hint ? '\n' + hint : ''));
            box.querySelector('[data-nl-sms-units]').textContent = c.units;
            box.querySelector('[data-nl-sms-segments]').textContent = c.segments;
            box.querySelector('[data-nl-sms-encoding]').textContent = c.encoding;
            box.querySelector('[data-nl-sms-remaining]').textContent = c.remaining;
            box.classList.toggle('nl-danger', max > 0 && c.segments > max);
        }
        area.addEventListener('input', update);
        update();
    }

    onReady(function () {
        Array.prototype.forEach.call(document.querySelectorAll('ul[data-cjwnl-tree]'), tree);
        Array.prototype.forEach.call(document.querySelectorAll('section#nl-job[data-url]'), job);
        Array.prototype.forEach.call(document.querySelectorAll('textarea[data-nl-sms-counter]'), smsCounter);
        Array.prototype.forEach.call(document.querySelectorAll('select[data-cjwnl-languages]'), updateLanguageSelector);
        Array.prototype.forEach.call(document.querySelectorAll('[data-cjwnl-sort-highlight]'), sortHighlight);
    });
})();
