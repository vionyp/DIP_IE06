// SemiSense — small progressive-enhancement script.
// The site works fully without JS (plain PHP forms); this just adds a
// nicer visual highlight when a quiz option is selected.
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
});
