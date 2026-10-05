{* The live length counter of an SMS text: a textarea with data-nl-sms-counter="<id of the counter>" (and optional
   data-nl-sms-hint, the line added to every SMS, and data-nl-sms-max, the most parts). It counts as
   CjwNewsletterSms::segments() does: GSM 03.38 (160 / 153 per part, extension characters twice) or UCS-2 (70 / 67).
   Without JavaScript the server's count of the last post is shown. *}
<script>
{literal}
(function () {
    var basic = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    var extended = "^{}\\[~]|€\f";
    function count(text) {
        var chars = Array.from(text), units = 0, gsm = true, i;
        for (i = 0; i < chars.length; i++) {
            if (basic.indexOf(chars[i]) !== -1) { units += 1; }
            else if (extended.indexOf(chars[i]) !== -1) { units += 2; }
            else { gsm = false; break; }
        }
        if (!gsm) { units = 0; for (i = 0; i < chars.length; i++) { units += chars[i].length; } }
        var single = gsm ? 160 : 70, part = gsm ? 153 : 67;
        var segments = units === 0 ? 0 : (units <= single ? 1 : Math.ceil(units / part));
        var per = segments <= 1 ? single : part;
        return { encoding: gsm ? 'GSM 7-bit' : 'Unicode', units: units, segments: segments, remaining: Math.max(segments, 1) * per - units };
    }
    Array.prototype.forEach.call(document.querySelectorAll('textarea[data-nl-sms-counter]'), function (area) {
        var box = document.getElementById(area.getAttribute('data-nl-sms-counter'));
        if (!box) { return; }
        var hint = area.getAttribute('data-nl-sms-hint') || '';
        var max = parseInt(area.getAttribute('data-nl-sms-max') || '0', 10);
        function update() {
            var text = area.value.replace(/\r\n/g, '\n').trim();
            var c = count(text + (hint ? '\n' + hint : ''));
            box.querySelector('[data-nl-sms-units]').textContent = c.units;
            box.querySelector('[data-nl-sms-segments]').textContent = c.segments;
            box.querySelector('[data-nl-sms-encoding]').textContent = c.encoding;
            box.querySelector('[data-nl-sms-remaining]').textContent = c.remaining;
            box.classList.toggle('nl-danger', max > 0 && c.segments > max);
        }
        area.addEventListener('input', update);
        update();
    });
})();
{/literal}
</script>
