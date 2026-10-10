// StockSense — small progressive-enhancement script.
// The site works fully without JS (plain PHP forms); this adds a nicer
// visual highlight when a quiz option is selected, tap-to-expand for the
// dashboard category cards, and the one-time disclaimer popup on the
// landing page.
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

    // ---- Login / register card: tabs, show-password, and the popup ----
    function showAuthTab(card, mode) {
        card.querySelectorAll('[data-auth-tab]').forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.authTab === mode);
        });
        card.querySelectorAll('[data-auth-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.authPanel !== mode;
        });
        var first = card.querySelector('[data-auth-panel="' + mode + '"] input:not([type="hidden"])');
        if (first) {
            first.focus();
        }
    }

    document.querySelectorAll('[data-auth-card]').forEach(function (card) {
        card.querySelectorAll('[data-auth-tab]').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                showAuthTab(card, tab.dataset.authTab);
            });
        });
        card.querySelectorAll('[data-auth-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.authToggle);
                var reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                btn.textContent = reveal ? 'Hide' : 'Show';
            });
        });
    });

    var authModal = document.getElementById('auth-modal');
    if (authModal) {
        var closeAuthModal = function () { authModal.hidden = true; };

        document.querySelectorAll('[data-auth-open]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                authModal.hidden = false;
                showAuthTab(authModal.querySelector('[data-auth-card]'), link.dataset.authOpen);
            });
        });
        authModal.querySelector('[data-auth-close]').addEventListener('click', closeAuthModal);
        authModal.addEventListener('click', function (e) {
            if (e.target === authModal) {
                closeAuthModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAuthModal();
            }
        });
    }

    // ---- Dashboard category cards ----
    // Hover and keyboard focus expand a card in CSS alone. This keeps each
    // card's aria-expanded in step, and where there is no hover (touch, or a
    // narrow window with the cards stacked) makes a tap open one card at a time.
    document.querySelectorAll('[data-expand-cards]').forEach(function (row) {
        var cards = Array.prototype.slice.call(row.querySelectorAll('.category-card'));
        var stacked = window.matchMedia('(hover: none), (max-width: 900px)');

        function isExpanded(card) {
            if (stacked.matches) {
                return card.classList.contains('is-open');
            }
            return card.matches(':hover') || card.querySelector(':focus-visible') !== null;
        }
        function sync() {
            cards.forEach(function (card) {
                card.querySelector('.card-toggle').setAttribute('aria-expanded', isExpanded(card) ? 'true' : 'false');
            });
        }

        // Only collapse the stacked cards once we know taps can reopen them.
        row.classList.add('is-enhanced');

        row.addEventListener('click', function (e) {
            var card = e.target.closest('.category-card');
            if (!stacked.matches || !card || e.target.closest('a')) {
                return;
            }
            var open = !card.classList.contains('is-open');
            cards.forEach(function (other) {
                other.classList.remove('is-open');
            });
            card.classList.toggle('is-open', open);
            sync();
        });
        ['mouseover', 'mouseout', 'focusin'].forEach(function (type) {
            row.addEventListener(type, sync);
        });
        row.addEventListener('focusout', function () {
            setTimeout(sync, 0);
        });
        stacked.addEventListener('change', function () {
            cards.forEach(function (card) {
                card.classList.remove('is-open');
            });
            sync();
        });
        sync();
    });

    // ---- Forms that delete something ask first (forum topics and replies) ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
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
