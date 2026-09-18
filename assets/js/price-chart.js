// Day -5 .. Day +1 line charts for quiz questions.
// Before submit: Day -5..0 are random filler values, stored in a hidden input so
// they are posted back. After submit: the same values are redrawn and Day +1 is
// added from the real price_change_stock (data-day1).
(function () {
    if (typeof Chart === 'undefined') return;

    var PRE_LABELS = ['Day -5', 'Day -4', 'Day -3', 'Day -2', 'Day -1', 'Day 0'];

    function randomHistory() {
        var out = [];
        for (var i = 0; i < PRE_LABELS.length; i++) {
            out.push(Math.round((Math.random() * 6 - 3) * 100) / 100); // -3% .. +3%
        }
        return out;
    }

    function parseHistory(raw) {
        if (!raw) return null;
        var parts = raw.split(',').map(Number);
        return parts.length === PRE_LABELS.length && parts.every(isFinite) ? parts : null;
    }

    document.querySelectorAll('canvas[data-price-chart]').forEach(function (canvas) {
        var id = canvas.dataset.priceChart;
        var day1 = canvas.dataset.day1;
        var revealed = day1 !== undefined;
        var history = parseHistory(canvas.dataset.history) || randomHistory();

        var hidden = document.querySelector('input[data-chart-history="' + id + '"]');
        if (hidden) hidden.value = history.join(',');

        var labels = PRE_LABELS.slice();
        var data = history.slice();
        if (revealed) {
            labels.push('Day +1');
            data.push(parseFloat(day1));
        }

        var day1Up = revealed && parseFloat(day1) >= 0;
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    borderColor: '#2b6cb0',
                    backgroundColor: '#2b6cb0',
                    tension: 0.25,
                    pointRadius: 4,
                    pointBackgroundColor: function (ctx) {
                        return revealed && ctx.dataIndex === labels.length - 1
                            ? (day1Up ? '#3fa66a' : '#d8664f')
                            : '#2b6cb0';
                    },
                    // Dash the final (real) segment so the reveal stands out.
                    segment: {
                        borderDash: function (ctx) {
                            return revealed && ctx.p1DataIndex === labels.length - 1 ? [6, 4] : undefined;
                        }
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) { return (c.parsed.y >= 0 ? '+' : '') + c.parsed.y.toFixed(2) + '%'; }
                        }
                    }
                },
                scales: {
                    y: { title: { display: true, text: 'Daily change (%)' } }
                }
            }
        });
    });
})();
