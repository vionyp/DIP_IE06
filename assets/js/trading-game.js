// StockSense — Trading Game (pages/game.php)
//
// Everything runs in the browser. The price is a simulated SOXX:
//   * a random walk most of the time, plus
//   * news shocks. Each headline in game_news has an `impact` from -3 to +3
//     (bearish ... bullish for the semiconductor sector). The headline's
//     CATEGORY decides how that impact plays out over time, following the
//     lessons — see IMPACT_PROFILES below.
//
// Time: 1 trading day = TICKS_PER_DAY ticks (15-minute steps, 9:30–16:00).
// At 1x speed a day lasts ~3 seconds. A game lasts up to GAME_DAYS days.
(function () {
    'use strict';

    var CFG = window.STOCKSENSE_GAME;
    if (!CFG) return;

    // ------------------------------------------------------------------
    // Tunable settings
    // ------------------------------------------------------------------
    var TICKS_PER_DAY  = 26;          // 15-minute steps from 9:30 to 16:00
    var GAME_DAYS      = 252;         // one trading year
    var HISTORY_DAYS   = 252;         // pre-game history so the Yearly view isn't empty
    var BASE_TICK_MS   = 3000 / TICKS_PER_DAY;  // 1x speed = 3 seconds per day
    var DAILY_VOL      = 0.015;       // ~1.5% typical daily move from randomness alone
    var DAILY_DRIFT    = 0.0002;      // tiny upward drift, like a real index
    var NEWS_GAP_MIN   = 3;           // trading days between headlines
    var NEWS_GAP_MAX   = 7;
    var MARGIN_CALL    = 0.5;         // short is force-closed if equity < 50% of short value

    // % move per impact point, by category. Macro news moves the WHOLE sector;
    // corporate news is mostly about one company, so the ETF barely notices.
    var CATEGORY_SCALE = {
        macro: 1.6, geopolitical: 1.3, earnings: 1.3, supply_chain: 1.1, corporate: 0.7
    };

    // How the move unfolds after a headline breaks (from the lessons):
    //   immediate  share of the move in the first `immTicks` ticks
    //   follow     extra share spread over the next `followDays` days
    //              (negative = partial reversal / fade)
    var IMPACT_PROFILES = {
        geopolitical: { immediate: 1.0,  immTicks: 3,  follow: -0.3, followDays: 8,
            how: 'Hits suddenly, like an overnight ban, then partly reverses as companies adapt.' },
        earnings:     { immediate: 0.8,  immTicks: 3,  follow: 0.2,  followDays: 4,
            how: 'Fast jump or drop on the surprise, then a smaller follow-through drift.' },
        macro:        { immediate: 0.55, immTicks: 8,  follow: 0.45, followDays: 6,
            how: 'Hits the whole sector, and keeps rippling for days as rates and demand reprice.' },
        supply_chain: { immediate: 0.35, immTicks: 10, follow: 0.65, followDays: 5,
            how: 'Small first reaction that builds over the next few days as it ripples through the supply chain.' },
        corporate:    { immediate: 1.0,  immTicks: 3,  follow: -0.5, followDays: 4,
            how: 'Mostly about one company, so the sector move is small and partly fades.' }
    };

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    var money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    function fmtMoney(v) { return money.format(v); }
    function fmtSigned(v) { return (v >= 0 ? '+' : '−') + money.format(Math.abs(v)); }
    function fmtPct(v) { return (v >= 0 ? '+' : '−') + Math.abs(v).toFixed(2) + '%'; }
    function fmtInt(v) { return Math.round(v).toLocaleString('en-US'); }
    function $(id) { return document.getElementById(id); }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function randn() { // standard normal (Box–Muller)
        var u = 0, v = 0;
        while (u === 0) u = Math.random();
        while (v === 0) v = Math.random();
        return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v);
    }
    function randInt(a, b) { return a + Math.floor(Math.random() * (b - a + 1)); }
    function shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = arr[i]; arr[i] = arr[j]; arr[j] = t;
        }
        return arr;
    }
    function timeLabel(tickInDay) {
        var mins = 9 * 60 + 30 + tickInDay * 15;
        var h = Math.floor(mins / 60), m = mins % 60;
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h > 12 ? h - 12 : h;
        return h12 + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
    // global tick index -> day number (day 1 = first game day; <= 0 = history)
    function dayOfGt(gt) { return Math.floor(gt / TICKS_PER_DAY) + 1; }
    function tickOfGt(gt) { return ((gt % TICKS_PER_DAY) + TICKS_PER_DAY) % TICKS_PER_DAY; }

    // ------------------------------------------------------------------
    // State
    // ------------------------------------------------------------------
    var S;

    function newState() {
        return {
            gt: 0,                 // next tick to simulate (global tick index)
            price: 0,
            startPrice: 0,         // price when the market opened on day 1
            prices: {},            // gt -> price (all ticks, history included)
            bars: [],              // daily OHLC: {day, o, h, l, c}
            pending: {},           // gt -> extra log-return from news effects
            volBoostUntil: -Infinity,
            newsSchedule: [],      // [{gt, item}], sorted
            newsSeen: [],          // [{item, gt, price, move}]
            cash: CFG.startCash,
            shares: 0,             // + long, - short
            avg: 0,
            realized: 0,
            trades: [],            // [{gt, delta, price, label}]
            running: false,
            started: false,
            ended: false,
            speed: 1,
            view: 'week'
        };
    }

    // One simulated 15-minute step. Returns the new price.
    function simulateTick(gt, prevPrice) {
        var sigma = DAILY_VOL / Math.sqrt(TICKS_PER_DAY);
        var mu = DAILY_DRIFT / TICKS_PER_DAY;
        var volMult = gt <= S.volBoostUntil ? 1.25 : 1;
        // overnight gap at the open is a little larger than an intraday step
        if (tickOfGt(gt) === 0) volMult *= 1.8;
        var r = mu + sigma * volMult * randn() + (S.pending[gt] || 0);
        delete S.pending[gt];
        return Math.max(1, prevPrice * Math.exp(r));
    }

    function recordTick(gt, price) {
        S.prices[gt] = price;
        var day = dayOfGt(gt);
        var bar = S.bars[S.bars.length - 1];
        if (!bar || bar.day !== day) {
            S.bars.push({ day: day, o: price, h: price, l: price, c: price });
        } else {
            bar.h = Math.max(bar.h, price);
            bar.l = Math.min(bar.l, price);
            bar.c = price;
        }
    }

    function buildHistory() {
        var p = 150 + Math.random() * 100;
        var firstGt = -HISTORY_DAYS * TICKS_PER_DAY;
        for (var gt = firstGt; gt < 0; gt++) {
            p = simulateTick(gt, p);
            recordTick(gt, p);
        }
        S.price = p;
        // drop tick-level detail older than a week to keep memory small
        for (var k in S.prices) {
            if (+k < -6 * TICKS_PER_DAY) delete S.prices[k];
        }
    }

    function scheduleNews() {
        var pool = shuffle(CFG.news.slice());
        var day = randInt(2, 3);
        var i = 0;
        while (day <= GAME_DAYS - 2 && i < pool.length) {
            var tick = randInt(1, TICKS_PER_DAY - 10); // early enough to react the same day
            S.newsSchedule.push({ gt: (day - 1) * TICKS_PER_DAY + tick, item: pool[i++] });
            day += randInt(NEWS_GAP_MIN, NEWS_GAP_MAX);
        }
    }

    // Turn a headline into future price nudges, starting on the NEXT tick
    // (so a player who is paying attention gets a chance to react).
    function applyNewsEffect(item, gt0) {
        var prof = IMPACT_PROFILES[item.category] || IMPACT_PROFILES.corporate;
        var scale = CATEGORY_SCALE[item.category] || 1;
        var move = item.impact === 0
            ? (Math.random() - 0.5) * 0.008                      // mixed news: small, random direction
            : item.impact * scale / 100 * (0.75 + Math.random() * 0.5);
        var i;
        for (i = 0; i < prof.immTicks; i++) {
            addPending(gt0 + i, move * prof.immediate / prof.immTicks);
        }
        var n = prof.followDays * TICKS_PER_DAY;
        for (i = 0; i < n; i++) {
            addPending(gt0 + prof.immTicks + i, move * prof.follow / n);
        }
        S.volBoostUntil = Math.max(S.volBoostUntil, gt0 + 2 * TICKS_PER_DAY);
        return move;
    }
    function addPending(gt, r) { S.pending[gt] = (S.pending[gt] || 0) + r; }

    // ------------------------------------------------------------------
    // Game loop
    // ------------------------------------------------------------------
    function step() {
        var gt = S.gt;
        if (dayOfGt(gt) > GAME_DAYS) { endGame('time'); return; }

        S.price = simulateTick(gt, S.price);
        recordTick(gt, S.price);
        if (gt === 0) S.startPrice = S.price;

        // Headlines scheduled for this tick
        while (S.newsSchedule.length && S.newsSchedule[0].gt <= gt) {
            var n = S.newsSchedule.shift();
            var seen = { item: n.item, gt: gt, price: S.price };
            seen.move = applyNewsEffect(n.item, gt + 1);
            S.newsSeen.push(seen);
            showNews(seen);
            if ($('pause-on-news').checked) {
                setRunning(false);
                flash('News just broke! Read it, decide, then press ▶ to continue.', 'info');
            }
        }

        S.gt++;
        checkMargin();
        dirty = true;
    }

    var ICON_PAUSE = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><rect x="2" y="1" width="3.5" height="12" rx="1" fill="currentColor"/><rect x="8.5" y="1" width="3.5" height="12" rx="1" fill="currentColor"/></svg>';
    var ICON_PLAY = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M3 1.5v11l9.5-5.5z" fill="currentColor"/></svg>';
    var dirty = true, lastTs = 0, acc = 0;
    function frame(ts) {
        if (S.running && !S.ended) {
            acc += Math.min(ts - lastTs, 500);
            var stepMs = BASE_TICK_MS / S.speed;
            var guard = 0;
            while (acc >= stepMs && guard++ < 40 && S.running && !S.ended) {
                step();
                acc -= stepMs;
            }
        }
        lastTs = ts;
        if (dirty) { render(); dirty = false; }
        requestAnimationFrame(frame);
    }

    function setRunning(on) {
        S.running = on && !S.ended;
        acc = 0;
        $('btn-play').innerHTML = S.running ? ICON_PAUSE : ICON_PLAY;
        $('btn-play').setAttribute('aria-label', S.running ? 'Pause' : 'Play');
        $('btn-play').classList.toggle('paused', !S.running);
    }

    // ------------------------------------------------------------------
    // Trading
    // ------------------------------------------------------------------
    function equity() { return S.cash + S.shares * S.price; }
    function buyingPower() { return Math.max(0, equity() - Math.abs(S.shares) * S.price); }
    // Most shares you can add in a direction without borrowing (no leverage):
    // after the trade, |position value| must not exceed net worth.
    function maxShares(dir) {
        var cap = Math.floor(equity() / S.price);
        return Math.max(0, dir > 0 ? cap - S.shares : cap + S.shares);
    }

    function execute(delta, note) {
        if (delta === 0) return;
        var p = S.price, old = S.shares, neu = old + delta, pnl = null;

        if (old !== 0 && Math.sign(delta) !== Math.sign(old)) {
            var closing = Math.min(Math.abs(delta), Math.abs(old));
            pnl = (p - S.avg) * closing * Math.sign(old);
            S.realized += pnl;
        }
        if (neu === 0) S.avg = 0;
        else if (old === 0 || Math.sign(neu) !== Math.sign(old)) S.avg = p;
        else if (Math.abs(neu) > Math.abs(old)) {
            S.avg = (S.avg * Math.abs(old) + p * Math.abs(delta)) / Math.abs(neu);
        }

        S.cash -= delta * p;
        S.shares = neu;

        var label;
        if (old >= 0 && delta > 0) label = (old === 0 ? 'Opened long' : 'Added to long');
        else if (old <= 0 && delta < 0) label = (old === 0 ? 'Opened short' : 'Added to short');
        else if (neu === 0) label = old > 0 ? 'Closed long' : 'Covered short';
        else if (Math.sign(neu) !== Math.sign(old)) label = neu > 0 ? 'Flipped to long' : 'Flipped to short';
        else label = old > 0 ? 'Sold part of long' : 'Covered part of short';
        if (note) label = note;

        S.trades.push({ gt: S.gt - 1, delta: delta, price: p, label: label, pnl: pnl });
        addTradeLog(S.trades[S.trades.length - 1]);
        dirty = true;
        return pnl;
    }

    function tradeAmount() {
        var v = parseFloat($('trade-amount').value);
        return isFinite(v) && v > 0 ? v : 0;
    }

    function trade(dir) {
        if (!S.started || S.ended) return;
        var amount = tradeAmount();
        if (amount <= 0) { flash('Enter a trade size in dollars first.', 'warn'); return; }
        var qty = Math.floor(amount / S.price);
        if (qty < 1) { flash('That is less than 1 share at ' + fmtMoney(S.price) + '.', 'warn'); return; }
        var max = maxShares(dir);
        if (max < 1) {
            flash(dir > 0 ? 'Not enough cash to go any more long.' : 'Not enough cash to back any more short selling.', 'warn');
            return;
        }
        var reduced = qty > max;
        qty = Math.min(qty, max);
        var pnl = execute(dir * qty);
        var msg = (dir > 0 ? 'Bought ' : 'Sold short ') + fmtInt(qty) + ' shares at ' + fmtMoney(S.price) + '.';
        if (pnl !== null) msg += ' Locked in ' + fmtSigned(pnl) + '.';
        if (reduced) msg += ' (Trade size cut to what your cash allows.)';
        flash(msg, pnl !== null && pnl < 0 ? 'bad' : 'good');
    }

    function closePosition() {
        if (!S.started || S.ended) return;
        if (S.shares === 0) { flash('You have no open position.', 'warn'); return; }
        var pnl = execute(-S.shares);
        flash('Position closed. Locked in ' + fmtSigned(pnl) + '.', pnl >= 0 ? 'good' : 'bad');
    }

    function checkMargin() {
        if (S.shares < 0 && equity() < MARGIN_CALL * Math.abs(S.shares) * S.price) {
            var pnl = execute(-S.shares, 'Margin call — short force-closed');
            flash('Margin call! The price rose so far that your short was closed automatically (' + fmtSigned(pnl) + ').', 'bad');
        }
        if (equity() <= 0) endGame('bust');
    }

    // ------------------------------------------------------------------
    // News feed
    // ------------------------------------------------------------------
    function showNews(seen) {
        var item = seen.item;
        var cat = CFG.categories[item.category] || { label: item.category, color: '#555', icon: '' };
        var empty = $('news-empty');
        if (empty) empty.remove();
        var el = document.createElement('article');
        el.className = 'news-card news-new';
        el.style.setProperty('--cat-color', cat.color);
        el.innerHTML =
            '<div class="news-meta">' +
                (cat.icon ? '<img src="' + esc(CFG.imgBase + cat.icon) + '" alt="">' : '') +
                '<span class="news-cat">' + esc(cat.label) + '</span>' +
                '<span class="news-time">Day ' + dayOfGt(seen.gt) + ' · ' + timeLabel(tickOfGt(seen.gt)) + '</span>' +
            '</div>' +
            '<h3>' + esc(item.headline) + '</h3>' +
            '<p>' + esc(item.summary) + '</p>';
        var feed = $('news-feed');
        feed.insertBefore(el, feed.firstChild);
        feed.scrollTop = 0;
        setTimeout(function () { el.classList.remove('news-new'); }, 6000);
    }

    // ------------------------------------------------------------------
    // Rendering: numbers
    // ------------------------------------------------------------------
    var lastFlashTimer = null;
    function flash(msg, kind) {
        var el = $('trade-message');
        el.textContent = msg;
        el.className = 'trade-message msg-' + (kind || 'info');
        clearTimeout(lastFlashTimer);
        lastFlashTimer = setTimeout(function () { el.textContent = ''; }, 7000);
    }

    function setUpDown(el, v) {
        el.classList.toggle('up', v > 0.004);
        el.classList.toggle('down', v < -0.004);
    }

    function render() {
        var curDay = Math.max(1, dayOfGt(Math.max(0, S.gt - 1)));
        var bars = S.bars;
        var today = bars[bars.length - 1];
        var prevClose = bars.length > 1 ? bars[bars.length - 2].c : today.o;
        if (!S.started) prevClose = today.o;

        $('price-now').textContent = fmtMoney(S.price);
        var ch = S.price - prevClose;
        var chEl = $('price-change');
        chEl.textContent = (ch >= 0 ? '▲ ' : '▼ ') + Math.abs(ch).toFixed(2) + ' (' + fmtPct(ch / prevClose * 100) + ') today';
        setUpDown(chEl, ch);

        var tick = S.started ? tickOfGt(Math.max(0, S.gt - 1)) : 0;
        $('clock-day').textContent = 'Day ' + curDay + ' of ' + GAME_DAYS + ' · Week ' + Math.ceil(curDay / 5);
        $('clock-time').textContent = timeLabel(tick);
        $('day-progress').style.width = ((tick + 1) / TICKS_PER_DAY * 100) + '%';

        var eq = equity();
        $('acct-equity').textContent = fmtMoney(eq);
        var ret = (eq - CFG.startCash) / CFG.startCash * 100;
        var retEl = $('acct-return');
        retEl.textContent = fmtPct(ret) + ' since start';
        setUpDown(retEl, ret);

        var bp = buyingPower();
        $('acct-cash').textContent = fmtMoney(bp);
        $('acct-cash-meter').style.width = Math.min(100, bp / Math.max(eq, 1) * 100) + '%';

        var sideEl = $('pos-side');
        var card = $('position-card');
        card.classList.remove('is-long', 'is-short');
        if (S.shares > 0) { sideEl.textContent = '▲ LONG'; card.classList.add('is-long'); }
        else if (S.shares < 0) { sideEl.textContent = '▼ SHORT'; card.classList.add('is-short'); }
        else sideEl.textContent = 'No position';

        $('pos-shares').textContent = fmtInt(Math.abs(S.shares));
        $('pos-avg').textContent = S.shares ? fmtMoney(S.avg) : '—';
        $('pos-value').textContent = fmtMoney(Math.abs(S.shares) * S.price);

        var upnl = (S.price - S.avg) * S.shares;
        var pnlPct = S.shares ? upnl / (Math.abs(S.shares) * S.avg) * 100 : 0;
        var pnlEl = $('pos-pnl');
        pnlEl.textContent = S.shares ? fmtSigned(upnl) + ' (' + fmtPct(pnlPct) + ')' : '$0.00';
        setUpDown($('pos-pnl-box'), S.shares ? upnl : 0);

        var realEl = $('acct-realized');
        realEl.textContent = fmtSigned(S.realized);
        setUpDown(realEl, S.realized);

        var amt = tradeAmount();
        $('trade-hint').textContent = '≈ ' + fmtInt(Math.floor(amt / S.price)) + ' shares at ' + fmtMoney(S.price);

        drawChart();
    }

    function addTradeLog(t) {
        var li = document.createElement('li');
        li.className = t.delta > 0 ? 'log-buy' : 'log-sell';
        li.innerHTML = '<span class="log-day">D' + dayOfGt(t.gt) + '</span> ' + esc(t.label) + ' · ' +
            fmtInt(Math.abs(t.delta)) + ' @ ' + fmtMoney(t.price) +
            (t.pnl !== null ? ' <strong class="' + (t.pnl >= 0 ? 'up' : 'down') + '">' + fmtSigned(t.pnl) + '</strong>' : '');
        var log = $('trade-log');
        log.insertBefore(li, log.firstChild);
    }

    // ------------------------------------------------------------------
    // Rendering: chart (plain canvas, no library)
    // ------------------------------------------------------------------
    var canvas, ctx, chartPoints = [], chartGeom = null;
    var COLORS = {
        line: '#2c4a7c', fillTop: 'rgba(44,74,124,0.18)', fillBottom: 'rgba(44,74,124,0)',
        up: '#2f8f5b', down: '#d8664f', grid: '#ece8df', text: '#6b6b6b', prev: '#b8b2a5'
    };

    // Build the series for the current view:
    //   day/week -> 15-minute ticks;  year -> daily candles
    function buildSeries() {
        var lastGt = S.gt - 1;
        if (S.view === 'year') {
            var bars = S.bars.slice(-252);
            return { kind: 'candles', bars: bars };
        }
        var days = S.view === 'day' ? 1 : 5;
        var curDayStartGt = (dayOfGt(lastGt) - 1) * TICKS_PER_DAY;
        var startGt = curDayStartGt - (days - 1) * TICKS_PER_DAY;
        var pts = [];
        for (var gt = startGt; gt <= lastGt; gt++) {
            if (S.prices[gt] !== undefined) pts.push({ gt: gt, p: S.prices[gt] });
        }
        return { kind: 'line', pts: pts, startGt: startGt, slots: days * TICKS_PER_DAY };
    }

    function niceStep(range, count) {
        var raw = range / count;
        var mag = Math.pow(10, Math.floor(Math.log10(raw)));
        var n = raw / mag;
        return (n < 1.5 ? 1 : n < 3 ? 2 : n < 7 ? 5 : 10) * mag;
    }

    function drawChart() {
        if (!canvas) return;
        var dpr = window.devicePixelRatio || 1;
        var w = canvas.clientWidth, h = canvas.clientHeight;
        if (canvas.width !== Math.round(w * dpr) || canvas.height !== Math.round(h * dpr)) {
            canvas.width = Math.round(w * dpr);
            canvas.height = Math.round(h * dpr);
        }
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, w, h);

        var pad = { l: 8, r: 64, t: 14, b: 26 };
        var pw = w - pad.l - pad.r, ph = h - pad.t - pad.b;
        var series = buildSeries();

        // y-range
        var lo = Infinity, hi = -Infinity;
        if (series.kind === 'candles') {
            series.bars.forEach(function (b) { lo = Math.min(lo, b.l); hi = Math.max(hi, b.h); });
        } else {
            series.pts.forEach(function (q) { lo = Math.min(lo, q.p); hi = Math.max(hi, q.p); });
        }
        var prevClose = null;
        if (S.view === 'day' && S.bars.length > 1) {
            prevClose = S.bars[S.bars.length - 2].c;
            lo = Math.min(lo, prevClose); hi = Math.max(hi, prevClose);
        }
        if (!isFinite(lo)) return;
        var spanPad = Math.max((hi - lo) * 0.08, S.price * 0.004);
        lo -= spanPad; hi += spanPad;
        function y(p) { return pad.t + (hi - p) / (hi - lo) * ph; }

        // grid + price labels
        ctx.font = '11px -apple-system, "Segoe UI", Roboto, sans-serif';
        ctx.textBaseline = 'middle';
        var stepV = niceStep(hi - lo, 5);
        for (var g = Math.ceil(lo / stepV) * stepV; g <= hi; g += stepV) {
            var gy = Math.round(y(g)) + 0.5;
            ctx.strokeStyle = COLORS.grid; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.l, gy); ctx.lineTo(pad.l + pw, gy); ctx.stroke();
            if (Math.abs(gy - y(S.price)) > 14) {
                ctx.fillStyle = COLORS.text; ctx.textAlign = 'left';
                ctx.fillText(g.toFixed(stepV < 1 ? 2 : 0), pad.l + pw + 8, gy);
            }
        }

        chartPoints = [];
        var xOfGt = null, xOfDay = null;

        if (series.kind === 'line') {
            var slotW = pw / Math.max(series.slots - 1, 1);
            xOfGt = function (gt) { return pad.l + (gt - series.startGt) * slotW; };

            // day separators / time labels
            ctx.textAlign = 'center'; ctx.textBaseline = 'top'; ctx.fillStyle = COLORS.text;
            var nDays = series.slots / TICKS_PER_DAY;
            for (var d = 0; d < nDays; d++) {
                var dayStart = series.startGt + d * TICKS_PER_DAY;
                if (nDays > 1) {
                    var sx = Math.round(xOfGt(dayStart)) + 0.5;
                    if (d > 0) {
                        ctx.strokeStyle = COLORS.grid; ctx.setLineDash([3, 3]);
                        ctx.beginPath(); ctx.moveTo(sx, pad.t); ctx.lineTo(sx, pad.t + ph); ctx.stroke();
                        ctx.setLineDash([]);
                    }
                    ctx.fillText('Day ' + dayOfGt(dayStart), xOfGt(dayStart + TICKS_PER_DAY / 2), pad.t + ph + 8);
                } else {
                    [0, 6, 12, 18, 25].forEach(function (t) {
                        ctx.textAlign = t === 0 ? 'left' : t === 25 ? 'right' : 'center';
                        ctx.fillText(timeLabel(t).replace(' AM', 'am').replace(' PM', 'pm'), xOfGt(dayStart + t), pad.t + ph + 8);
                    });
                    ctx.textAlign = 'center';
                }
            }

            if (prevClose !== null) {
                var py = Math.round(y(prevClose)) + 0.5;
                ctx.strokeStyle = COLORS.prev; ctx.setLineDash([5, 4]);
                ctx.beginPath(); ctx.moveTo(pad.l, py); ctx.lineTo(pad.l + pw, py); ctx.stroke();
                ctx.setLineDash([]);
                ctx.fillStyle = COLORS.text; ctx.textAlign = 'left'; ctx.textBaseline = 'bottom';
                ctx.fillText('prev close', pad.l + 4, py - 2);
            }

            var pts = series.pts;
            if (pts.length) {
                var grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + ph);
                grad.addColorStop(0, COLORS.fillTop); grad.addColorStop(1, COLORS.fillBottom);
                ctx.beginPath();
                pts.forEach(function (q, i) {
                    var px = xOfGt(q.gt), qy = y(q.p);
                    if (i === 0) ctx.moveTo(px, qy); else ctx.lineTo(px, qy);
                    chartPoints.push({ x: px, p: q.p, label: 'Day ' + dayOfGt(q.gt) + ' · ' + timeLabel(tickOfGt(q.gt)) });
                });
                ctx.strokeStyle = COLORS.line; ctx.lineWidth = 2; ctx.lineJoin = 'round';
                ctx.stroke();
                ctx.lineTo(xOfGt(pts[pts.length - 1].gt), pad.t + ph);
                ctx.lineTo(xOfGt(pts[0].gt), pad.t + ph);
                ctx.closePath(); ctx.fillStyle = grad; ctx.fill();
                ctx.lineWidth = 1;
            }
        } else {
            var bars = series.bars;
            var n = 252;
            var bw = pw / n;
            var firstIndex = n - bars.length;
            var firstDay = bars.length ? bars[0].day : 0;
            xOfDay = function (day) { return pad.l + (firstIndex + (day - firstDay) + 0.5) * bw; };
            ctx.textAlign = 'center'; ctx.textBaseline = 'top'; ctx.fillStyle = COLORS.text;
            bars.forEach(function (b) {
                var cx = xOfDay(b.day);
                var color = b.c >= b.o ? COLORS.up : COLORS.down;
                ctx.strokeStyle = color; ctx.fillStyle = color;
                ctx.beginPath(); ctx.moveTo(Math.round(cx) + 0.5, y(b.h)); ctx.lineTo(Math.round(cx) + 0.5, y(b.l)); ctx.stroke();
                var top = y(Math.max(b.o, b.c)), bot = y(Math.min(b.o, b.c));
                ctx.fillRect(cx - Math.max(bw * 0.35, 0.5), top, Math.max(bw * 0.7, 1), Math.max(bot - top, 1));
                chartPoints.push({ x: cx, p: b.c, label: 'Day ' + b.day + ' · O ' + b.o.toFixed(2) + ' H ' + b.h.toFixed(2) + ' L ' + b.l.toFixed(2) + ' C' });
                if (((b.day - 1) % 42 + 42) % 42 === 0) {
                    ctx.fillStyle = COLORS.text;
                    ctx.fillText('Day ' + b.day, cx, pad.t + ph + 8);
                }
            });
            // mark where the game started
            if (S.started && xOfDay(1) > pad.l) {
                var gx = Math.round(xOfDay(1) - bw / 2) + 0.5;
                ctx.strokeStyle = COLORS.prev; ctx.setLineDash([4, 4]);
                ctx.beginPath(); ctx.moveTo(gx, pad.t); ctx.lineTo(gx, pad.t + ph); ctx.stroke();
                ctx.setLineDash([]);
                ctx.fillStyle = COLORS.text; ctx.textAlign = 'left'; ctx.textBaseline = 'top';
                ctx.fillText('game start', gx + 4, pad.t + 2);
            }
        }

        // map a tick to x for markers in any view
        function markerX(gt) {
            if (xOfGt) {
                var x = xOfGt(gt);
                return x >= pad.l - 1 && x <= pad.l + pw + 1 ? x : null;
            }
            var d = dayOfGt(gt), x2 = xOfDay(d);
            return x2 >= pad.l ? x2 : null;
        }

        // news markers along the bottom
        S.newsSeen.forEach(function (s) {
            var mx = markerX(s.gt);
            if (mx === null) return;
            var cat = CFG.categories[s.item.category];
            ctx.fillStyle = cat ? cat.color : '#888';
            ctx.beginPath(); ctx.arc(mx, pad.t + ph - 5, 4, 0, Math.PI * 2); ctx.fill();
        });

        // trade markers
        S.trades.forEach(function (t) {
            var tx = markerX(t.gt);
            if (tx === null) return;
            var ty = y(t.price);
            ctx.fillStyle = t.delta > 0 ? COLORS.up : COLORS.down;
            ctx.beginPath();
            if (t.delta > 0) { ctx.moveTo(tx, ty + 4); ctx.lineTo(tx - 6, ty + 14); ctx.lineTo(tx + 6, ty + 14); }
            else { ctx.moveTo(tx, ty - 4); ctx.lineTo(tx - 6, ty - 14); ctx.lineTo(tx + 6, ty - 14); }
            ctx.closePath(); ctx.fill();
        });

        // average entry line
        if (S.shares !== 0 && S.avg > lo && S.avg < hi) {
            var ay = Math.round(y(S.avg)) + 0.5;
            ctx.strokeStyle = S.shares > 0 ? COLORS.up : COLORS.down;
            ctx.setLineDash([2, 3]);
            ctx.beginPath(); ctx.moveTo(pad.l, ay); ctx.lineTo(pad.l + pw, ay); ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = ctx.strokeStyle; ctx.textAlign = 'left'; ctx.textBaseline = 'bottom';
            ctx.fillText('your entry', pad.l + 4, ay - 2);
        }

        // current price tag
        var cy = y(S.price);
        var up = S.bars.length < 2 || S.price >= S.bars[S.bars.length - 2].c;
        ctx.fillStyle = up ? COLORS.up : COLORS.down;
        ctx.fillRect(pad.l + pw + 2, cy - 9, pad.r - 4, 18);
        ctx.fillStyle = '#fff'; ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
        ctx.font = 'bold 11px -apple-system, "Segoe UI", Roboto, sans-serif';
        ctx.fillText(S.price.toFixed(2), pad.l + pw + 7, cy);

        chartGeom = { pad: pad, pw: pw, ph: ph, y: y };
        drawHover();
    }

    var hoverX = null;
    function drawHover() {
        var tip = $('chart-tooltip');
        if (hoverX === null || !chartPoints.length || !chartGeom) { tip.hidden = true; return; }
        var best = null, bd = Infinity;
        chartPoints.forEach(function (p) {
            var d = Math.abs(p.x - hoverX);
            if (d < bd) { bd = d; best = p; }
        });
        if (!best || bd > 30) { tip.hidden = true; return; }
        var g = chartGeom;
        ctx.strokeStyle = 'rgba(0,0,0,0.25)'; ctx.setLineDash([2, 2]);
        ctx.beginPath(); ctx.moveTo(Math.round(best.x) + 0.5, g.pad.t); ctx.lineTo(Math.round(best.x) + 0.5, g.pad.t + g.ph); ctx.stroke();
        ctx.setLineDash([]);
        ctx.fillStyle = COLORS.line;
        ctx.beginPath(); ctx.arc(best.x, g.y(best.p), 3.5, 0, Math.PI * 2); ctx.fill();
        tip.hidden = false;
        tip.textContent = best.label + ' ' + fmtMoney(best.p);
        var left = Math.min(Math.max(best.x - tip.offsetWidth / 2, 0), canvas.clientWidth - tip.offsetWidth);
        tip.style.left = left + 'px';
    }

    // ------------------------------------------------------------------
    // Start / end
    // ------------------------------------------------------------------
    function startGame() {
        $('start-screen').hidden = true;
        S.started = true;
        setRunning(true);
        flash('Market is open. Good luck!', 'info');
    }

    function endGame(reason) {
        if (S.ended) return;
        setRunning(false);
        if (S.shares !== 0) execute(-S.shares, 'Auto-closed at game end');
        S.ended = true;

        var lastGt = Math.max(0, S.gt - 1);
        var finalValue = equity();
        var ret = (finalValue - CFG.startCash) / CFG.startCash * 100;
        var buyHold = S.startPrice ? (S.price / S.startPrice - 1) * 100 : 0;
        var daysPlayed = Math.max(1, Math.min(GAME_DAYS, dayOfGt(lastGt)));
        var tradesCount = S.trades.filter(function (t) { return t.label.indexOf('Auto-closed') !== 0; }).length;
        var beat = ret > buyHold;

        var reasonText = reason === 'time' ? 'The trading year is over.'
            : reason === 'bust' ? 'Your account ran out of money.'
            : 'You closed the trading floor on day ' + daysPlayed + '.';

        var review = S.newsSeen.map(function (s) {
            var later = S.prices[s.gt + TICKS_PER_DAY];
            var endP = later !== undefined ? later : S.price;
            var chg = (endP / s.price - 1) * 100;
            var push = (Math.exp(s.move) - 1) * 100;
            var cat = CFG.categories[s.item.category] || { label: s.item.category, color: '#555' };
            var prof = IMPACT_PROFILES[s.item.category];
            var lean = s.item.impact > 0 ? '<span class="lean up">Bullish</span>'
                : s.item.impact < 0 ? '<span class="lean down">Bearish</span>'
                : '<span class="lean">Mixed</span>';
            return '<li style="--cat-color:' + esc(cat.color) + '">' +
                '<div class="review-head"><span class="news-cat">' + esc(cat.label) + '</span>' +
                '<span class="news-time">Day ' + dayOfGt(s.gt) + '</span>' + lean +
                '<span class="review-move ' + (chg >= 0 ? 'up' : 'down') + '">' + fmtPct(chg) +
                (later !== undefined ? ' next day' : ' until game end') + '</span></div>' +
                '<p class="review-headline">' + esc(s.item.headline) + '</p>' +
                '<p class="review-why"><strong>Why:</strong> ' + esc(s.item.explanation) +
                (prof ? ' <em>' + esc(prof.how) + '</em>' : '') + '</p>' +
                '<p class="review-push">This headline pushed the price ≈ ' + fmtPct(push) + ' in total (spread over the following days). Everything else was random market noise.</p>' +
                '</li>';
        }).join('');

        $('end-content').innerHTML =
            '<img class="overlay-mascot" src="' + esc(CFG.imgBase + (ret >= 0 ? 'mascot-correct.png' : 'mascot-incorrect.png')) + '" alt="">' +
            '<h1>' + (ret >= 0 ? 'Market closed, nice trading!' : 'Market closed. Tough year.') + '</h1>' +
            '<p class="end-reason">' + esc(reasonText) + '</p>' +
            '<div class="end-stats">' +
                '<div><span class="stat-label">Final net worth</span><span class="stat-value">' + fmtMoney(finalValue) + '</span></div>' +
                '<div><span class="stat-label">Your return</span><span class="stat-value ' + (ret >= 0 ? 'up' : 'down') + '">' + fmtPct(ret) + '</span></div>' +
                '<div><span class="stat-label">Just holding SOXX</span><span class="stat-value ' + (buyHold >= 0 ? 'up' : 'down') + '">' + fmtPct(buyHold) + '</span></div>' +
                '<div><span class="stat-label">Trades · Days · Headlines</span><span class="stat-value">' + tradesCount + ' · ' + daysPlayed + ' · ' + S.newsSeen.length + '</span></div>' +
            '</div>' +
            '<p class="end-verdict">' + (beat
                ? 'You <strong>beat</strong> simply buying and holding the ETF by ' + (ret - buyHold).toFixed(2) + ' percentage points.'
                : 'Simply buying and holding the ETF would have done ' + (buyHold - ret).toFixed(2) + ' percentage points better. Timing the market is hard!') + '</p>' +
            '<div class="highscores" id="end-highscores"><p class="muted">Saving your result…</p></div>' +
            (review ? '<h2>How the news moved the market</h2><ol class="news-review">' + review + '</ol>'
                    : '<p class="muted">No headlines broke while you were trading. Try playing longer next time.</p>') +
            '<div class="end-actions"><button class="btn btn-primary" id="btn-again">Play again</button> ' +
            '<a class="btn btn-secondary" href="dashboard.php">Back to dashboard</a></div>';
        $('end-screen').hidden = false;
        $('btn-again').addEventListener('click', function () { window.location.reload(); });

        saveResult({
            final_value: Math.round(finalValue * 100) / 100,
            buy_hold_pct: Math.round(buyHold * 100) / 100,
            days_played: daysPlayed,
            trades: tradesCount,
            news_seen: S.newsSeen.length
        });
        dirty = true;
    }

    function saveResult(payload) {
        var box = $('end-highscores');
        fetch(CFG.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.ok) throw new Error(data.error || 'save failed');
            var rows = data.high_scores.map(function (hs, i) {
                var mine = +hs.id === data.id;
                var r = +hs.return_pct;
                return '<tr' + (mine ? ' class="mine"' : '') + '><td>' + (i + 1) + '</td><td>' + fmtMoney(+hs.final_value) +
                    '</td><td class="' + (r >= 0 ? 'up' : 'down') + '">' + fmtPct(r) + '</td><td>' + hs.days_played + '</td></tr>';
            }).join('');
            box.innerHTML = '<h3>Best games' + (data.rank <= 5 ? ' (you placed #' + data.rank + '!)' : '') + '</h3>' +
                '<table><thead><tr><th>#</th><th>Final value</th><th>Return</th><th>Days</th></tr></thead><tbody>' + rows + '</tbody></table>';
        }).catch(function () {
            box.innerHTML = '<p class="muted">Couldn\'t save this game to the high-score table.</p>';
        });
    }

    // ------------------------------------------------------------------
    // Wiring
    // ------------------------------------------------------------------
    function init() {
        S = newState();
        canvas = $('price-chart');
        ctx = canvas.getContext('2d');
        buildHistory();
        scheduleNews();

        $('btn-start').addEventListener('click', startGame);
        $('btn-play').addEventListener('click', function () {
            if (!S.started) { startGame(); return; }
            setRunning(!S.running);
        });
        document.querySelectorAll('.speed-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                S.speed = +b.dataset.speed;
                document.querySelectorAll('.speed-btn').forEach(function (o) { o.classList.toggle('active', o === b); });
            });
        });
        document.querySelectorAll('.view-tab').forEach(function (b) {
            b.addEventListener('click', function () {
                S.view = b.dataset.view;
                document.querySelectorAll('.view-tab').forEach(function (o) { o.classList.toggle('active', o === b); });
                dirty = true;
            });
        });
        document.querySelectorAll('.preset-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                var pct = +b.dataset.pct;
                $('trade-amount').value = Math.floor(equity() * pct / 100);
                dirty = true;
            });
        });
        $('trade-amount').addEventListener('input', function () { dirty = true; });
        $('btn-long').addEventListener('click', function () { trade(1); });
        $('btn-short').addEventListener('click', function () { trade(-1); });
        $('btn-close').addEventListener('click', closePosition);
        $('btn-end').addEventListener('click', function () {
            if (!S.started) return;
            endGame('player');
        });

        canvas.addEventListener('mousemove', function (e) {
            hoverX = e.clientX - canvas.getBoundingClientRect().left;
            dirty = true;
        });
        canvas.addEventListener('mouseleave', function () { hoverX = null; dirty = true; });
        window.addEventListener('resize', function () { dirty = true; });

        document.addEventListener('keydown', function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;
            if (!S.started || S.ended) return;
            var k = e.key.toLowerCase();
            if (k === ' ') { e.preventDefault(); setRunning(!S.running); }
            else if (k === 'l') trade(1);
            else if (k === 's') trade(-1);
            else if (k === 'c') closePosition();
        });

        setRunning(false);
        dirty = true;
        requestAnimationFrame(function (ts) { lastTs = ts; frame(ts); });
    }

    // exposed for debugging in the browser console: StockSenseGame.state()
    window.StockSenseGame = { state: function () { return S; } };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
