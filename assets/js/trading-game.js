// StockSense — Trading Game (pages/game.php)
//
// Everything runs in the browser. The game replays REAL history:
//   * CFG.prices holds real SOXX daily candles [date, open, high, low, close]
//     (table game_soxx_daily). Each game picks a random ~1-year window.
//   * CFG.news holds the real headlines from news_articles. published_at is
//     the day the story happened, and the lessons/quizzes measure the
//     reaction in the FOLLOWING session (price_change_stock/_sector). So a
//     headline breaks after the close of its publish day — before the next
//     session opens — and the player can trade at that close. Weekend or
//     holiday stories break before the next session too.
//   * Real dates are hidden ("Day 1, Day 2…") until the end screen.
//
// Time: 1 trading day = TICKS_PER_DAY ticks (15-minute steps, 9:30–16:00).
// Real data is daily, so the path INSIDE a day is simulated — but every day
// still opens, touches its high and low, and closes at the real SOXX prices.
// At 1x speed a day lasts ~3 seconds. A game lasts up to GAME_DAYS days.
(function () {
    'use strict';

    var CFG = window.STOCKSENSE_GAME;
    if (!CFG || !CFG.prices || CFG.prices.length < 300) return;

    // ------------------------------------------------------------------
    // Tunable settings
    // ------------------------------------------------------------------
    var TICKS_PER_DAY  = 26;          // 15-minute steps from 9:30 to 16:00
    var GAME_DAYS      = 252;         // one trading year
    var HISTORY_DAYS   = 252;         // real days shown before the game starts (Yearly view)
    var BASE_TICK_MS   = 3000 / TICKS_PER_DAY;  // 1x speed = 3 seconds per day
    var MARGIN_CALL    = 0.5;         // short is force-closed if equity < 50% of short value

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
    function timeLabel(tickInDay) {
        var mins = 9 * 60 + 30 + tickInDay * 15;
        var h = Math.floor(mins / 60), m = mins % 60;
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h > 12 ? h - 12 : h;
        return h12 + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    function fmtDate(iso) { // '2024-03-07' -> 'Mar 7, 2024'
        var p = iso.split('-');
        return MONTHS[+p[1] - 1] + ' ' + (+p[2]) + ', ' + p[0];
    }
    // global tick index -> day number (day 1 = first game day; <= 0 = history)
    function dayOfGt(gt) { return Math.floor(gt / TICKS_PER_DAY) + 1; }
    function tickOfGt(gt) { return ((gt % TICKS_PER_DAY) + TICKS_PER_DAY) % TICKS_PER_DAY; }

    // ------------------------------------------------------------------
    // Real data: choose the window, map days <-> real candles
    // ------------------------------------------------------------------
    var P = CFG.prices;   // [[date, o, h, l, c], ...] oldest first
    var W = 0;            // index in P of game day 1

    function candle(day) { return P[W + day - 1]; }          // real candle for a game day
    function realDate(day) { var c = candle(day); return c ? c[0] : null; }

    // Index of the first trading day on/after an ISO date (news on a weekend
    // or holiday breaks on the next trading day).
    function tradingIndexOnOrAfter(iso) {
        var lo = 0, hi = P.length;
        while (lo < hi) {
            var mid = (lo + hi) >> 1;
            if (P[mid][0] < iso) lo = mid + 1; else hi = mid;
        }
        return lo;
    }

    function chooseWindow() {
        var news = CFG.news;
        var firstNews = news.length ? reactionIndex(news[0].published_date) : 0;
        var lastNews = news.length ? reactionIndex(news[news.length - 1].published_date) : P.length - 1;
        // The whole year should sit inside the stretch that has real headlines.
        var minStart = Math.max(firstNews, 1);
        var maxStart = Math.min(lastNews, P.length - 1) - GAME_DAYS + 1;
        if (maxStart < minStart) {                // not enough headline history: use any full year
            minStart = 1;
            maxStart = P.length - GAME_DAYS;
        }
        if (maxStart < minStart) {                // less than a year of prices at all
            GAME_DAYS = P.length - 1;
            maxStart = minStart = 1;
        }
        W = randInt(minStart, maxStart);
        HISTORY_DAYS = Math.min(HISTORY_DAYS, W);
    }

    // Simulated 15-minute path for one real day: starts at the real open,
    // touches the real high and low at random times, ends at the real close.
    function dayPath(c) {
        var T = TICKS_PER_DAY, o = c[1], h = c[2], l = c[3], cl = c[4];
        var a = randInt(1, T - 2), b;
        do { b = randInt(1, T - 2); } while (b === a);
        var anchors = [[0, o], [a, h], [b, l], [T - 1, cl]].sort(function (x, y) { return x[0] - y[0]; });
        var path = new Array(T);
        var sigma = Math.max(h - l, o * 0.002) * 0.18;
        for (var s = 0; s < anchors.length - 1; s++) {
            var i0 = anchors[s][0], p0 = anchors[s][1], i1 = anchors[s + 1][0], p1 = anchors[s + 1][1];
            var n = i1 - i0, walk = [0];
            for (var k = 1; k <= n; k++) walk.push(walk[k - 1] + randn() * sigma);
            for (k = 0; k <= n; k++) {
                var t = k / n;
                var v = p0 + (p1 - p0) * t + (walk[k] - t * walk[n]);   // Brownian bridge between anchors
                path[i0 + k] = Math.min(h, Math.max(l, v));
            }
        }
        path[0] = o; path[a] = h; path[b] = l; path[T - 1] = cl;
        return path;
    }

    // ------------------------------------------------------------------
    // State
    // ------------------------------------------------------------------
    var S;

    function newState() {
        return {
            gt: 0,                 // next tick to play (global tick index)
            price: 0,
            startPrice: 0,         // previous close when the game started
            prices: {},            // gt -> price (ticks for the week before the start + all game ticks)
            bars: [],              // daily OHLC: {day, o, h, l, c}
            paths: {},             // day -> simulated intraday path through the real candle
            newsByDay: {},         // day -> [news items]
            newsReleased: {},      // day -> true once that morning's headlines are out
            newsSeen: [],          // [{item, gt, day, price}]
            premarket: false,      // paused before the open with fresh headlines
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

    function pathFor(day) {
        if (!S.paths[day]) S.paths[day] = dayPath(candle(day));
        return S.paths[day];
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
        // Real daily candles before day 1 (for the Yearly view) …
        for (var d = 1 - HISTORY_DAYS; d <= 0; d++) {
            var c = candle(d);
            S.bars.push({ day: d, o: c[1], h: c[2], l: c[3], c: c[4] });
        }
        // … plus 15-minute ticks for the last 5 of them (Daily/Weekly views).
        for (d = Math.max(1 - HISTORY_DAYS, -4); d <= 0; d++) {
            var path = pathFor(d);
            for (var t = 0; t < TICKS_PER_DAY; t++) S.prices[(d - 1) * TICKS_PER_DAY + t] = path[t];
        }
        S.price = candle(0)[4];
        S.startPrice = S.price;
    }

    // Trading day (index into P) whose session is the first one AFTER the story.
    function reactionIndex(iso) {
        var j = tradingIndexOnOrAfter(iso);
        return (P[j] && P[j][0] === iso) ? j + 1 : j;
    }

    function scheduleNews() {
        CFG.news.forEach(function (item) {
            var day = reactionIndex(item.published_date) - W + 1;
            if (day < 1 || day > GAME_DAYS) return;
            (S.newsByDay[day] = S.newsByDay[day] || []).push(item);
        });
    }

    // ------------------------------------------------------------------
    // Game loop
    // ------------------------------------------------------------------
    function step() {
        var gt = S.gt;
        var day = dayOfGt(gt), tick = tickOfGt(gt);
        if (day > GAME_DAYS) { endGame('time'); return; }

        // Before the open: headlines from the previous session (or the
        // weekend) break now, so the player can trade at the last close.
        if (tick === 0 && !S.newsReleased[day]) {
            S.newsReleased[day] = true;
            var items = S.newsByDay[day] || [];
            items.forEach(function (item) {
                var seen = { item: item, gt: gt, day: day, price: S.price };
                S.newsSeen.push(seen);
                showNews(seen);
            });
            if (items.length && $('pause-on-news').checked) {
                S.premarket = true;
                setRunning(false);
                flash((items.length > 1 ? items.length + ' headlines' : 'A headline') +
                    ' broke after the last close! Read, decide, then press ▶ to open the market.', 'info');
                dirty = true;
                return;
            }
        }

        S.premarket = false;
        S.price = pathFor(day)[tick];
        recordTick(gt, S.price);
        if (tick === 0) {
            // remember which way the player was positioned when the bell rang
            S.newsSeen.forEach(function (seen) {
                if (seen.day === day) seen.position = Math.sign(S.shares);
            });
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
    // Years in a headline would give away when the game is set, so during
    // play they become relative: "growth in 2024" -> "growth in [next year]".
    function maskYears(text, day) {
        var nowYear = +realDate(day).slice(0, 4);
        return String(text).replace(/\b(19[89]\d|20[0-4]\d)\b/g, function (y) {
            var d = +y - nowYear;
            if (d === 0) return '[this year]';
            if (d === 1) return '[next year]';
            if (d === -1) return '[last year]';
            return d > 0 ? '[in ' + d + ' years]' : '[' + (-d) + ' years ago]';
        });
    }

    // During play only the headline is shown: the article summaries often
    // say how the stock reacted, which would give the answer away. The full
    // summary, real date and source link appear on the end screen.
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
                '<span class="news-time">Before Day ' + seen.day + ' opens</span>' +
            '</div>' +
            '<h3>' + esc(maskYears(item.headline, seen.day)) + '</h3>' +
            (item.ticker ? '<p class="news-company"><span class="news-ticker">' + esc(item.ticker) + '</span> ' +
                esc(item.company || '') + '</p>' : '');
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

        $('price-now').textContent = fmtMoney(S.price);
        var ch = S.price - prevClose;
        var chEl = $('price-change');
        chEl.textContent = (ch >= 0 ? '▲ ' : '▼ ') + Math.abs(ch).toFixed(2) + ' (' + fmtPct(ch / prevClose * 100) + ') today';
        setUpDown(chEl, ch);

        var tick = S.started ? tickOfGt(Math.max(0, S.gt - 1)) : 0;
        $('clock-day').textContent = 'Day ' + curDay + ' of ' + GAME_DAYS + ' · Week ' + Math.ceil(curDay / 5);
        if (S.premarket) {
            curDay = dayOfGt(S.gt);
            $('clock-day').textContent = 'Day ' + curDay + ' of ' + GAME_DAYS + ' · Week ' + Math.ceil(curDay / 5);
        }
        $('clock-time').textContent = S.premarket ? 'Pre-market (opens 9:30 AM)' : timeLabel(tick);
        $('day-progress').style.width = S.premarket ? '0%' : ((tick + 1) / TICKS_PER_DAY * 100) + '%';

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

        var periodText = 'You were trading real SOXX prices from <strong>' + esc(fmtDate(realDate(1))) +
            '</strong> to <strong>' + esc(fmtDate(realDate(daysPlayed))) + '</strong>.';

        var review = S.newsSeen.map(function (s) {
            var item = s.item;
            var cat = CFG.categories[item.category] || { label: item.category, color: '#555' };
            var c = candle(s.day), prev = candle(s.day - 1);
            var played = s.position !== undefined;              // did the next session open?
            var dayMove = (c[4] / prev[4] - 1) * 100;
            var moveHtml = played
                ? '<span class="review-move ' + (dayMove >= 0 ? 'up' : 'down') + '">SOXX ' + fmtPct(dayMove) + ' next session</span>'
                : '<span class="review-move">Game ended before the open</span>';
            var call = '';
            if (played) {
                if (s.position === 0) call = '<span class="lean">You were flat</span>';
                else {
                    var right = (s.position > 0) === (dayMove >= 0);
                    call = '<span class="lean ' + (right ? 'up' : 'down') + '">You were ' +
                        (s.position > 0 ? 'long' : 'short') + (right ? ' ✓' : ' ✗') + '</span>';
                }
            }
            var stockMove = item.price_change_stock !== null && item.ticker
                ? ' ' + esc(item.ticker) + ' itself moved ' + fmtPct(item.price_change_stock) + ' in that session.'
                : '';
            // the summaries were written for quizzes; drop the quiz-only sentence
            var summary = String(item.summary)
                .replace(/[;,]\s*(but |and )?(this|the) question is about the following session\./gi, '.')
                .replace(/,\s*and the following session is the focus of this question\./gi, '.')
                .replace(/\s*(The|This) question is about the following session\./gi, '')
                .trim();
            return '<li style="--cat-color:' + esc(cat.color) + '">' +
                '<div class="review-head"><span class="news-cat">' + esc(cat.label) + '</span>' +
                '<span class="news-time">' + esc(fmtDate(item.published_date)) + ' · traded on Day ' + s.day + '</span>' +
                call + moveHtml + '</div>' +
                '<p class="review-headline">' + esc(item.headline) + '</p>' +
                '<p class="review-summary">' + esc(summary) + stockMove + '</p>' +
                (item.explanation ? '<p class="review-why"><strong>Lesson:</strong> ' + esc(item.explanation) + '</p>' : '') +
                (item.source_url ? '<p class="review-source"><a href="' + esc(item.source_url) +
                    '" target="_blank" rel="noopener">Read the original article</a></p>' : '') +
                '</li>';
        }).join('');

        $('end-content').innerHTML =
            '<img class="overlay-mascot mascot-animated" src="' + esc(CFG.imgBase + 'animations/' + (ret >= 0 ? 'mascot-correct-natural-v5.gif' : 'mascot-incorrect-natural-v5.gif')) + '" alt="">' +
            '<h1>' + (ret >= 0 ? 'Market closed, nice trading!' : 'Market closed. Tough year.') + '</h1>' +
            '<p class="end-reason">' + esc(reasonText) + ' ' + periodText + '</p>' +
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
            (review ? '<h2>The real headlines, and how SOXX reacted</h2><ol class="news-review">' + review + '</ol>'
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
        chooseWindow();
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
