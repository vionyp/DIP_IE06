// StockSense — small progressive-enhancement script.
// The site works fully without JS (plain PHP forms); this adds a nicer
// visual highlight when a quiz option is selected, and the one-time
// disclaimer popup on the landing page.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.quiz-question').forEach(function (fieldset) {
        fieldset.querySelectorAll('input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', function () {
                fieldset.querySelectorAll('.quiz-option').forEach(function (label) {
                    label.classList.remove('selected');
                });
                input.closest('.quiz-option').classList.add('selected');
            });
        });
    });

    var modal = document.getElementById('disclaimer-modal');
    if (modal) {
        var ackKey = 'stocksense_disclaimer_ack';
        if (!localStorage.getItem(ackKey)) {
            modal.hidden = false;
        }
        var ackBtn = document.getElementById('disclaimer-ack');
        if (ackBtn) {
            ackBtn.addEventListener('click', function () {
                localStorage.setItem(ackKey, '1');
                modal.hidden = true;
            });
        }
    }
});
