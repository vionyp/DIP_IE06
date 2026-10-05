-- =====================================================================
-- StockSense — Trading Game tables (pages/game.php)
--
-- Run once, AFTER sql/schema.sql, on both fresh and existing databases.
-- Safe to re-run: tables use IF NOT EXISTS and the news rows are upserted.
--
--   phpMyAdmin: select the stocksense database -> Import -> this file,
--               with "Character set of the file" = utf8mb4.
--   CLI: mysql --default-character-set=utf8mb4 -u root stocksense < sql/migrations/002_trading_game.sql
-- =====================================================================

USE stocksense;

-- ---------------------------------------------------------------------
-- Game news — the same articles as news_articles, rewritten so players
-- can't look up what happened: real company names are replaced by
-- fictional ones (e.g. Micron -> Company ABC), and product names, people
-- and years are made generic.
--
--   impact      -3..+3, how bearish/bullish the headline is for the whole
--               semiconductor sector (the SOXX-style chart in the game),
--               judged from the lessons. 0 = mixed/neutral. The category
--               decides HOW the price reacts (sudden shock vs. slow
--               ripple) — see IMPACT_PROFILES in assets/js/trading-game.js.
--   explanation one-line lesson takeaway shown in the end-of-game review.
--
-- Company name key (keep consistent when adding rows):
--   Micron = Company ABC   Nvidia = Company DEF    TSMC = Company GHI
--   Intel = Company JKL    Samsung = Company MNO   ASML = Company PQR
--   SK Hynix = Company STU Qualcomm = Company VWX  Huawei = Company YZA
--   Tower Semi = Company BCD  Broadcom = Company EFG  Apple = Company HIJ
--   Amkor = Company KLM
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS game_news (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    source_article_id  INT NULL,
    category           ENUM('geopolitical','earnings','macro','corporate','supply_chain') NOT NULL,
    headline           VARCHAR(255) NOT NULL,
    summary            TEXT NOT NULL,
    impact             TINYINT NOT NULL DEFAULT 0,
    explanation        VARCHAR(255) NOT NULL,
    is_active          TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (source_article_id) REFERENCES news_articles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- One row per finished game, for the high-score table.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS game_results (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    start_cash       DECIMAL(12,2) NOT NULL,
    final_value      DECIMAL(12,2) NOT NULL,
    return_pct       DECIMAL(8,2) NOT NULL,
    buy_hold_pct     DECIMAL(8,2) NOT NULL,   -- what simply holding SOXX would have returned
    days_played      INT NOT NULL,
    trades           INT NOT NULL,
    news_seen        INT NOT NULL,
    played_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO game_news (id, source_article_id, category, headline, summary, impact, explanation) VALUES
(1, 1, 'geopolitical', 'US tightens export rules on advanced AI chips to China',
 'New export controls restrict sales of Company DEF''s most advanced AI accelerators to Chinese customers, raising questions about how much revenue is exposed to the region.',
 -2, 'Export bans hit overnight: a big slice of sales to a major market can vanish in a single day.'),

(2, 2, 'earnings', 'Company GHI beats quarterly estimates on strong AI chip demand',
 'The world''s largest contract chipmaker reported revenue and margins ahead of analyst expectations, citing sustained demand for advanced-node chips used in AI accelerators.',
 2, 'An earnings beat versus expectations lifts the stock, and the largest foundry''s strength signals demand across the sector.'),

(3, 3, 'macro', 'Global semiconductor sales rise for the eighth straight month',
 'Industry data shows sector-wide sales climbing again, driven mainly by AI-related demand offsetting softer consumer electronics demand.',
 2, 'Industry-wide sales data moves nearly every chip stock at once — a steady growth streak lifts the whole sector.'),

(4, 4, 'corporate', 'Company JKL announces new foundry partnership and cost-cutting plan',
 'Company JKL unveiled a restructuring plan alongside a new external foundry partnership intended to accelerate its turnaround in advanced manufacturing.',
 1, 'Restructuring that cuts costs and adds a partner is read as a step toward a turnaround — mildly positive.'),

(5, 5, 'supply_chain', 'Company PQR flags delayed shipments after key component shortage',
 'The lithography machine maker said a shortage of a critical component from a sub-supplier will delay some machine shipments into the next quarter.',
 -1, 'One supplier''s problem ripples forward: delayed tools slow the chipmakers waiting for them.'),

(6, 6, 'earnings', 'Company ABC guidance beats expectations on memory pricing recovery',
 'Company ABC raised its forward guidance, citing a recovering pricing environment for memory chips used in both PCs and data centers.',
 2, 'Forward guidance matters more than past results — a raised outlook signals the memory cycle is turning up.'),

(7, 7, 'geopolitical', 'US announces $8.5 billion grant to Company JKL for chip plants',
 'The largest single grant awarded under the US CHIPS and Science Act.',
 1, 'Government subsidies lower the cost of building fabs, which investors welcome.'),

(8, 8, 'geopolitical', 'US awards Company GHI $6.6 billion subsidy for Arizona chip production',
 'Company GHI commits to building a third fab in Arizona with federal backing.',
 1, 'Subsidies reduce the huge cost of new factories and lower geographic risk — a modest positive.'),

(9, 9, 'geopolitical', 'US to grant Company MNO $6.4 billion for Texas chip manufacturing',
 'Funding directed towards advanced logic fabs in Taylor, Texas.',
 1, 'Government grants make expansion cheaper for chipmakers — a modest positive for the sector.'),

(10, 10, 'geopolitical', 'Company ABC receives $6.1 billion CHIPS Act grant to build New York and Idaho fabs',
 'Subsidies targeted at massive memory manufacturing complexes.',
 1, 'Subsidies cut the cost of capacity expansion — supportive for chip stocks.'),

(11, 11, 'geopolitical', 'China fails Company ABC''s products in security review, bars some purchases',
 'China''s cyberspace regulator banned Company ABC from critical infrastructure.',
 -2, 'A government blacklist cuts off customers overnight — and raises fears other chipmakers could be next.'),

(12, 12, 'geopolitical', 'Company PQR cancels shipments of some machines to China after US request',
 'The Dutch government partially revoked export licenses for some of its advanced lithography machines.',
 -2, 'Export bans cancel orders that were already scheduled — equipment makers are hit first.'),

(13, 13, 'geopolitical', 'US restricts exports of advanced Company DEF AI chips to Middle Eastern countries',
 'Expanded export rules designed to prevent diversion of technology to China.',
 -1, 'Wider export restrictions shrink the market a chip designer can legally sell to.'),

(14, 14, 'geopolitical', 'US tightens export controls on AI chips to China, hitting Company DEF',
 'Updated rules lower the performance threshold, catching several of Company DEF''s chips that were designed to comply with earlier rules.',
 -2, 'Export bans are sudden shocks — products that were legal yesterday can be banned today.'),

(15, 15, 'geopolitical', 'Company GHI opens its first Japan chip plant in Kumamoto',
 'Backed by heavy Japanese government subsidies to revitalize local industry.',
 1, 'Subsidized factories in new countries lower costs and reduce concentration risk in one location.'),

(16, 16, 'geopolitical', 'Germany approves €5 billion subsidy for Company GHI''s European microchip plant',
 'The EU Chips Act in action, aimed at securing Europe''s automotive chip supply.',
 1, 'Government money for new fabs reduces the cost of expansion — mildly positive.'),

(17, 17, 'geopolitical', 'Germany and Company JKL agree on €10 billion subsidy for chip plant',
 'A major win for the EU''s domestic manufacturing goals.',
 1, 'A large subsidy lowers the cost of a very expensive factory — mildly positive.'),

(18, 18, 'geopolitical', 'US revokes Company JKL, Company VWX licenses to sell to China''s Company YZA',
 'A direct hit to Company JKL''s PC-chip sales in China.',
 -2, 'Revoked export licenses mean lost sales overnight for the chipmakers involved.'),

(19, 19, 'geopolitical', 'France''s antitrust agency raids Company DEF offices in cloud probe',
 'European technological sovereignty concerns driving regulatory scrutiny.',
 -1, 'Regulatory probes add uncertainty and possible fines for the sector''s biggest name.'),

(20, 20, 'geopolitical', 'Company JKL scraps $5.4 billion acquisition after China fails to approve',
 'Geopolitical tensions cited as the main reason China''s regulator never signed off on Company JKL''s purchase of Company BCD.',
 -1, 'Geopolitics can block deals entirely — a failed acquisition wastes time and strategy.'),

(21, 21, 'geopolitical', 'US grants indefinite waiver for Company MNO, Company STU to supply China fabs',
 'Relief for major memory operations located in China.',
 2, 'Policy easing: a waiver to keep operating lets investors breathe a sigh of relief.'),

(22, 22, 'geopolitical', 'Company GHI says it received US waiver to continue China operations',
 'Allows Company GHI to maintain tools at its Nanjing fabrication plant.',
 1, 'Special permits remove the worst-case risk of being forced out of a market.'),

(23, 23, 'geopolitical', 'US pressures Netherlands to stop Company PQR servicing China equipment',
 'Escalation from bans on new machines to bans on servicing existing ones.',
 -2, 'Escalating export controls threaten service revenue on machines already sold.'),

(24, 24, 'geopolitical', 'US signs executive order restricting US investments in China tech',
 'Impacts the investment arms of US chipmakers operating in Asia.',
 -1, 'Rising US–China tension adds risk for chipmakers with exposure to China.'),

(25, 25, 'geopolitical', 'EU reaches agreement on €43 billion Chips Act',
 'Paves the way for Company GHI and Company JKL to finalize European expansion plans.',
 1, 'Large government support programs make new fabs cheaper to build.'),

(26, 26, 'geopolitical', 'Company PQR accuses former employee in China of stealing trade secrets',
 'Highlights ongoing intellectual property theft concerns in the tech rivalry.',
 -1, 'IP theft fears add to geopolitical risk, a small negative.'),

(27, 27, 'geopolitical', 'Company ABC announces $2.75 billion semiconductor plant in India',
 'A significant win for the US–India technology corridor.',
 1, 'Diversifying factory locations lowers concentration risk — a small positive.'),

(28, 28, 'geopolitical', 'China restricts exports of chipmaking metals gallium and germanium',
 'Retaliatory export controls impacting upstream materials supply.',
 -2, 'When a country controls a key raw material, restrictions ripple through the whole chip supply chain.'),

(29, 29, 'geopolitical', 'Company MNO to invest $290 million in Japan for advanced chip packaging facility',
 'Bridging South Korea–Japan tech relations, backed by Japanese government subsidies.',
 1, 'Subsidized investment and better cross-border ties are mildly positive.'),

(30, 30, 'geopolitical', 'Dutch government to restrict semiconductor tech exports, hitting Company PQR',
 'Aligning with US rules to restrict older lithography systems as well.',
 -2, 'Export restrictions cancel future orders for equipment makers — a sudden negative.'),

(31, 31, 'geopolitical', 'US Commerce Secretary tells Company DEF: US will not let you bypass China chip curbs',
 'Direct warning about redesigned AI chips built to comply with export rules.',
 -1, 'Even ''compliant'' redesigned chips face political risk, limiting the rebound.'),

(32, 32, 'earnings', 'Company DEF earnings smash estimates, stock splits 10-for-1',
 'Data center revenue rose more than 400% year-over-year on the AI boom.',
 3, 'A huge earnings beat versus expectations from the sector''s leader lifts the whole sector.'),

(33, 33, 'earnings', 'Company DEF revenue up 265% on booming AI demand',
 'The CEO declares generative AI has hit the ''tipping point''.',
 3, 'Blowout results plus confident management commentary drive a strong reaction.'),

(34, 34, 'earnings', 'Company DEF reports record quarterly revenue of $13.5 billion',
 'Guided significantly higher for next quarter, suggesting AI demand is structural, not a fad.',
 2, 'Strong forward guidance tells investors growth will continue — a positive surprise.'),

(35, 35, 'earnings', 'Company DEF issues blowout forecast as AI chips drive massive growth',
 'The forecast that launched the company toward a trillion-dollar valuation.',
 3, 'Guidance far above expectations is one of the strongest positive catalysts.'),

(36, 36, 'earnings', 'Company JKL issues weak guidance, shares tumble',
 'Foundry business operating losses dragged heavily on overall margins.',
 -2, 'Weak forward guidance disappoints expectations — guidance often matters more than results.'),

(37, 37, 'earnings', 'Company JKL shares plunge as forecast falls far short of expectations',
 'Legacy PC and server markets remained softer than management anticipated.',
 -2, 'Missing expectations badly causes a sharp drop, and signals weak PC and server demand.'),

(38, 38, 'earnings', 'Company JKL beats earnings estimates, signals PC market recovery',
 'Brief optimism as its PC-chip group showed stabilization.',
 1, 'Beating expectations plus a recovery signal is positive for the sector.'),

(39, 39, 'earnings', 'Company JKL reports largest quarterly loss in company history',
 'Massive inventory corrections caused a historic earnings miss.',
 -2, 'A historic miss caused by an inventory glut is a warning about the whole chip cycle.'),

(40, 40, 'earnings', 'Company GHI beats profit estimates, flags strong AI chip demand',
 'Maintained its full-year revenue growth target of 20% to 25%.',
 2, 'A beat plus steady guidance from the largest foundry signals healthy demand.'),

(41, 41, 'earnings', 'Company GHI projects return to solid growth next year',
 'Signaled the bottoming out of the global smartphone and PC inventory cycle.',
 2, 'Inventory digestion ending means orders resume — bullish for the whole sector.'),

(42, 42, 'earnings', 'Company GHI posts first profit drop in four years, cuts annual outlook',
 'Citing persistent weakness in China and a delayed US fab rollout.',
 -2, 'A cut to guidance is a strong negative signal, especially from the industry''s biggest foundry.'),

(43, 43, 'earnings', 'Company GHI warns of inventory glut, sees revenue drop in first half',
 'The semiconductor super-cycle has fully reversed into a classic down-cycle.',
 -2, 'Inventory gluts mean customers stop ordering — a classic down-cycle signal.'),

(44, 44, 'earnings', 'Company MNO operating profit jumps more than 900% on memory chip recovery',
 'Memory pricing recovered strongly due to AI data center buildouts.',
 2, 'A memory price recovery shows the cycle turning up — positive for chip stocks.'),

(45, 45, 'earnings', 'Company MNO flags weak earnings as memory chip demand remains sluggish',
 'Consumer electronics divisions failed to offset foundry operating losses.',
 -1, 'Weak results from a giant memory maker mean the downturn is not over yet.'),

(46, 46, 'earnings', 'Company MNO expects chip demand to recover slowly after 95% profit plunge',
 'Its worst quarterly performance in over a decade.',
 -2, 'A collapse in profit with only a slow recovery expected weighs on the sector.'),

(47, 47, 'earnings', 'Company MNO posts worst profit in 14 years, says memory output cuts to continue',
 'A historic pivot for a company that rarely cuts production during downcycles.',
 -1, 'Bad results, but cutting production helps clear excess inventory — a mixed, mildly negative signal.'),

(48, 48, 'earnings', 'Company ABC delivers surprise profit, driven by AI memory demand',
 'Its AI memory is sold out through next year, driving massive revenue beats.',
 2, 'A surprise profit beats expectations — sold-out AI memory signals strong demand.'),

(49, 49, 'earnings', 'Company ABC issues upbeat revenue forecast on stabilizing memory prices',
 'Signaled the memory sector had successfully cleared excess inventory.',
 2, 'Upbeat guidance plus cleared inventory means the cycle is turning up.'),

(50, 50, 'earnings', 'Company ABC issues wider-than-expected loss forecast',
 'Recovery taking longer than expected as server demand lagged.',
 -2, 'Guidance worse than expectations pushes the recovery further out.'),

(51, 51, 'earnings', 'Company ABC posts worst quarterly loss on record, writes down inventory',
 'A $1.4 billion inventory write-down highlighted the depth of the PC crash.',
 -2, 'Inventory write-downs show the depth of the down-cycle.'),

(52, 52, 'earnings', 'Company PQR misses order estimates, shares drop',
 'Net bookings fell sharply to €3.6 billion, missing the €5.4 billion consensus.',
 -2, 'Equipment orders are an early warning: fewer orders mean chipmakers are cutting expansion.'),

(53, 53, 'earnings', 'Company PQR reports record quarterly orders, boosted by advanced chip demand',
 'Bookings tripled from the prior quarter, showing strong demand for new capacity.',
 2, 'Record equipment orders mean chipmakers expect strong future demand.'),

(54, 54, 'earnings', 'Company PQR warns next year''s sales will be flat as chip industry struggles',
 'Acknowledged delayed orders from major clients including Company GHI and Company JKL.',
 -2, 'Flat guidance and delayed orders signal chipmakers are slowing expansion.'),

(55, 55, 'earnings', 'Company PQR raises annual outlook on strong Chinese demand for older machines',
 'China rushed to import equipment ahead of expected export bans.',
 1, 'Raised guidance is positive — though demand pulled forward before a ban may not last.'),

(56, 56, 'earnings', 'Company DEF earnings beat, but gaming revenue drops 51%',
 'Data center strength offset a sharp slump in gaming chips.',
 0, 'A beat with a weak segment is a mixed result — the market can go either way.'),

(57, 57, 'earnings', 'Company DEF posts 69% sales growth but flags bigger China export-control hit',
 'Restrictions on one China-specific chip cost about $2.5 billion of sales and the company expects a further $8 billion hit next quarter.',
 -1, 'Strong growth, but guidance hurt by export controls — guidance often outweighs results.'),

(58, 58, 'earnings', 'Company GHI heads into earnings with record profit expected on AI demand',
 'Expectations are high; the earnings call will provide next year''s guidance.',
 1, 'High expectations are already partly priced in — the reaction depends on beating them.'),

(59, 59, 'earnings', 'Company PQR bookings miss estimates as tariffs cloud outlook',
 'Net bookings were €3.9 billion, below estimates; the company kept annual guidance but warned tariffs increased uncertainty.',
 -1, 'An order miss plus tariff uncertainty is a negative, softened by unchanged guidance.'),

(60, 60, 'earnings', 'Company JKL posts deeper loss and forecasts steeper loss next quarter',
 'Revenue of $12.9 billion beat estimates, but losses were worse than expected amid major restructuring.',
 -1, 'Revenue beat, but weaker profit guidance disappoints — guidance wins.'),

(61, 61, 'earnings', 'Company ABC raises revenue, profit and margin forecasts as AI memory demand strengthens',
 'Revenue guidance rose to $11.2 billion and gross-margin guidance to 44.5%, supported by AI memory and stronger pricing.',
 2, 'Raised guidance across every metric is a strong positive surprise.'),

(62, 62, 'macro', 'Federal Reserve raises rates by 0.25%, acknowledges inflation is easing',
 'Tech stocks rallied as the Fed Chair mentioned a ''disinflationary process''.',
 1, 'A smaller hike with signs inflation is easing hints that rate hikes are nearly over.'),

(63, 63, 'macro', 'Fed hikes rates by 0.25% despite banking crisis',
 'A higher cost of capital keeps pressure on companies that spend heavily on factories.',
 -2, 'Higher rates make borrowing costlier and reduce what investors pay for future profits.'),

(64, 64, 'macro', 'Federal Reserve raises rates, drops language hinting at future hikes',
 'Signaled a pause, providing relief to global growth stocks.',
 1, 'A hint that hikes are ending lowers expected future rates — good for growth stocks.'),

(65, 65, 'macro', 'Fed approves hike that takes rates to highest level in more than 22 years',
 'The final hike of the cycle, setting the policy rate at 5.25%–5.50%.',
 -1, 'Higher rates weigh on valuations, though the end of hikes was largely expected.'),

(66, 66, 'macro', 'Fed holds rates steady, sparking massive tech and bond rally',
 'Bond yields plummeted, boosting the valuations of chip stocks.',
 2, 'Lower yields raise the value of future profits — growth stocks like chips benefit most.'),

(67, 67, 'macro', 'Fed holds rates steady, keeps forecast for three cuts next year',
 'Supports investment plans for companies that buy expensive equipment.',
 2, 'Expected rate cuts lower the cost of capital for the whole sector.'),

(68, 68, 'macro', 'Industry association reports global semiconductor sales fell 21%',
 'Confirmed the severity of the cyclical downturn.',
 -2, 'Industry-wide sales data moves every chip stock — a sharp decline confirms the down-cycle.'),

(69, 69, 'macro', 'Industry association says global chip sales rose month-to-month',
 'The first official confirmation that the chip market had bottomed.',
 2, 'Sector data showing the bottom is in — the cycle is turning up.'),

(70, 70, 'macro', 'Industry association forecasts 13% growth for global semiconductor sales next year',
 'A strong outlook driven by AI and data center investment.',
 2, 'Sector-wide growth forecasts lift expectations for nearly every chipmaker.'),

(71, 71, 'macro', 'Research firm reports global PC shipments plunged 30%',
 'The worst decline in PC history heavily impacted memory chip demand.',
 -2, 'Falling end demand leads to inventory gluts and fewer chip orders.'),

(72, 72, 'macro', 'Research firm says PC shipments grew, ending historic slump',
 'Inventory digestion has finished, allowing factory orders to resume.',
 2, 'End of inventory digestion: customers start ordering chips again.'),

(73, 73, 'macro', 'US inflation hotter than expected',
 'Hot inflation data stalls the tech sector rally.',
 -2, 'Hot inflation means rates stay high for longer — bad for growth-stock valuations.'),

(74, 74, 'macro', 'Ratings agency downgrades US credit rating',
 'The downgrade sent shockwaves through global stock markets.',
 -2, 'Macro shocks hit the whole market at once, including chip stocks.'),

(75, 75, 'macro', 'Fed signals rates will stay higher for longer, sparking tech selloff',
 'Long-term bond yields surged, squeezing valuations across the sector.',
 -3, '''Higher for longer'' raises the cost of capital and cuts what investors pay for future growth.'),

(76, 76, 'macro', 'Bank collapse sparks global banking contagion fears',
 'A freeze in venture funding heavily impacted early-stage hardware startups.',
 -2, 'Financial panic hits risky assets first, and tighter credit slows spending.'),

(77, 77, 'macro', 'Taiwan export orders plunge for 8th consecutive month',
 'Reflects severe weakness in global electronics demand.',
 -2, 'Weak electronics demand means fewer chip orders across the supply chain.'),

(78, 78, 'macro', 'US inflation rises 3.5%, pushing back rate cut hopes',
 'Caused a short-term pullback in high-valuation AI stocks.',
 -2, 'Hotter inflation delays rate cuts — bad for growth stocks.'),

(79, 79, 'macro', 'Fed projects just one rate cut this year as inflation persists',
 'Borrowing costs stay high for companies funding expensive new fabs.',
 -1, 'Fewer expected cuts mean higher borrowing costs for longer.'),

(80, 80, 'macro', 'US economy grows a surprisingly strong 3.3%',
 'A soft landing looks achieved, boosting confidence in business tech spending.',
 1, 'A ''soft landing'' supports business spending on chips and servers.'),

(81, 81, 'macro', 'US inflation comes in cooler than expected',
 'Triggered one of the largest single-day chip sector rallies of the year.',
 3, 'Cooler inflation raises hopes of rate cuts — growth stocks rally hard.'),

(82, 82, 'macro', 'Global manufacturing activity contracts for 11th straight month',
 'Industrial and auto chip demand began to show signs of softening.',
 -2, 'Weak manufacturing means lower demand for industrial and auto chips.'),

(83, 83, 'macro', 'Fed pivots, signaling three rate cuts next year',
 'Drove a big rotation of money into memory and tech stocks.',
 3, 'A pivot to rate cuts lowers the cost of capital — a major sector-wide catalyst.'),

(84, 84, 'macro', 'US economy adds 209,000 jobs as labor market cools',
 'Solid but cooling growth supports continued business IT upgrades.',
 1, 'A cooling-but-solid economy supports a soft landing — mildly positive.'),

(85, 85, 'macro', 'Inflation index shows prices unchanged for the month',
 'Confirms the disinflation trend, supporting higher valuations.',
 2, 'Falling inflation makes rate cuts more likely — positive for growth stocks.'),

(86, 86, 'macro', 'China approves 1 trillion yuan bond issue to boost its economy',
 'A stimulus package aimed at reviving lagging consumer electronics demand.',
 1, 'Stimulus can boost electronics demand in a major market — mildly positive.'),

(87, 87, 'corporate', 'Company JKL to cut 15% of workforce, suspend dividend in turnaround push',
 'A massive restructuring to save $10 billion following deep foundry losses.',
 -1, 'Deep cuts and a suspended dividend signal serious trouble, even if costs fall.'),

(88, 88, 'corporate', 'Company DEF announces 10-for-1 stock split following earnings beat',
 'Aimed at making shares more accessible to employees and retail investors.',
 1, 'Splits don''t change company value but can boost demand and signal confidence.'),

(89, 89, 'corporate', 'Company PQR CEO retires, longtime executive takes the helm',
 'A smooth transition at the top of the only maker of the most advanced lithography machines.',
 0, 'A planned, smooth leadership change usually causes little reaction.'),

(90, 90, 'corporate', 'Company GHI Chairman to retire next year',
 'The current CEO is slated to succeed him, consolidating leadership.',
 0, 'An orderly succession plan is mostly a non-event for the sector.'),

(91, 91, 'corporate', 'Company ABC cuts executive pay by up to 20% amid severe industry downturn',
 'An aggressive cash-saving move mirroring layoffs across the sector.',
 -1, 'Cost-cutting signals management expects the downturn to last.'),

(92, 92, 'corporate', 'Company MNO workers declare indefinite strike',
 'A historic labor action seeking better pay and vacation benefits.',
 -1, 'A strike at a major chipmaker creates production uncertainty.'),

(93, 93, 'corporate', 'Company JKL to run its programmable-chip unit as a standalone business',
 'A spinoff aimed at unlocking shareholder value through an eventual IPO.',
 1, 'Spinoffs can unlock hidden value — a small positive.'),

(94, 94, 'corporate', 'Company DEF board authorizes $25 billion in share buybacks',
 'Signals strong management confidence in future cash flow.',
 1, 'Buybacks reduce share count and signal confidence.'),

(95, 95, 'corporate', 'Company MNO replaces head of semiconductor business to overcome crisis',
 'A new chief is brought in to fix lagging competitiveness in AI memory.',
 0, 'A leadership change in a crisis is uncertain — it could help or hurt.'),

(96, 96, 'corporate', 'Company GHI board approves $3.8 billion investment for new factory in Germany',
 'A joint venture with three European chip and auto-parts firms for automotive chips.',
 1, 'Joint ventures share the cost and secure future customers for new capacity.'),

(97, 97, 'corporate', 'Company JKL acquires automotive chip startup',
 'Part of a broader push into electric-vehicle power management.',
 0, 'A small acquisition rarely moves the whole sector.'),

(98, 98, 'corporate', 'Company ABC breaks ground on massive new memory fab in Idaho',
 'The first new memory fab built in the US in 20 years.',
 1, 'New capacity signals confidence in long-term demand.'),

(99, 99, 'corporate', 'Company PQR announces new €12 billion share buyback program',
 'Reflects large order backlogs that give clear visibility into future cash flows.',
 1, 'A big buyback signals confidence and returns cash to shareholders.'),

(100, 100, 'corporate', 'Company JKL to restructure manufacturing to operate like a separate foundry',
 'Its factories will report their own profit and loss.',
 1, 'Restructuring for transparency can unlock value — mildly positive.'),

(101, 101, 'corporate', 'Company MNO announces 300 trillion won investment for massive chip hub in South Korea',
 'An aggressive challenge to the leading foundry, supported by the South Korean government.',
 1, 'Massive CapEx signals confidence in long-term chip demand, though it hurts short-term margins.'),

(102, 102, 'corporate', 'Company GHI promotes two executives to co-chief operating officers',
 'A leadership reshuffle to manage complex global capacity expansion.',
 0, 'Routine leadership moves rarely move the sector.'),

(103, 103, 'corporate', 'Company JKL slashes dividend by 65% to conserve cash for turnaround',
 'A historic cut to one of tech''s most reliable dividends.',
 -1, 'Dividend cuts signal financial strain and disappoint income investors.'),

(104, 104, 'corporate', 'Company DEF to acquire GPU software startup',
 'A software acquisition to strengthen its enterprise AI platform.',
 0, 'Small, strategic acquisitions usually have little sector impact.'),

(105, 105, 'corporate', 'Company ABC to sell a factory as it exits a failed memory technology',
 'Wrapping up a failed joint memory venture with Company JKL.',
 0, 'Exiting a failed business cleans up the balance sheet — mostly neutral.'),

(106, 106, 'corporate', 'Company PQR signs joint research agreement with Company MNO for new R&D facility in Korea',
 'Secures long-term collaboration on next-generation lithography.',
 1, 'Long-term partnerships secure future demand for advanced tools.'),

(107, 107, 'corporate', 'Company JKL to sell $1.5 billion stake in its self-driving chip unit',
 'A share sale to raise much-needed cash for factory spending.',
 0, 'Selling assets to raise cash shows strain but funds the turnaround — mixed.'),

(108, 108, 'corporate', 'Company GHI board approves $3.5 billion capital injection for Arizona subsidiary',
 'Funding needed to keep construction on pace at the troubled US site.',
 0, 'Extra funding for a delayed project is mixed news.'),

(109, 109, 'corporate', 'Company MNO borrows $16 billion from its own display unit to fund chip investment',
 'An unusual internal loan that highlights how expensive chipmaking has become.',
 0, 'Shows commitment to investing, but also the heavy cash needs of the industry.'),

(110, 110, 'corporate', 'Company ABC expands stock buyback program as memory market recovers',
 'Restarting shareholder returns after a brutal year of saving cash.',
 1, 'Restarting buybacks signals management believes the recovery is real.'),

(111, 111, 'corporate', 'Company JKL agrees to sell 20% stake in its mask-making equipment unit',
 'The deal with a private equity firm values the unit at $4.3 billion.',
 0, 'Selling a stake raises cash — a small, mostly neutral move.'),

(112, 112, 'supply_chain', 'Taiwan earthquake halts Company GHI production, highlights supply chain risks',
 'A 7.4 magnitude quake triggered automatic tool shutdowns, putting wafers at risk.',
 -2, 'Factory location risk: a disaster at the key chip hub ripples through the whole supply chain.'),

(113, 113, 'supply_chain', 'Company JKL delays $20 billion Ohio fab timeline by two years',
 'Slow subsidy payouts and a weak PC market forced schedule delays.',
 -1, 'Delays signal weak demand and slow capacity plans.'),

(114, 114, 'supply_chain', 'Company GHI delays Arizona fab production due to skilled worker shortage',
 'A blow to US domestic manufacturing timelines.',
 -1, 'Labor shortages delay new capacity and raise costs.'),

(115, 115, 'supply_chain', 'Company GHI delays second Arizona fab by several years',
 'Subsidy negotiations and soft demand cited as main reasons.',
 -1, 'Delayed expansion due to soft demand is a mild negative.'),

(116, 116, 'supply_chain', 'Company PQR ships industry''s first next-generation lithography system to Company JKL',
 'A critical milestone for Company JKL''s advanced manufacturing roadmap.',
 1, 'New technology milestones support future capacity and equipment sales.'),

(117, 117, 'supply_chain', 'Company MNO makes meaningful cut to memory chip production',
 'An unprecedented step to clear massive inventory gluts.',
 1, 'Supply cuts help clear inventory and push memory prices back up.'),

(118, 118, 'supply_chain', 'Company ABC says Taiwan earthquake will impact a small share of quarterly supply',
 'Memory production briefly halted, tightening global supply.',
 -1, 'A disaster at a key chip hub disrupts supply, though the hit is limited.'),

(119, 119, 'supply_chain', 'Company DEF warns advanced packaging is the main bottleneck for AI chips',
 'Company GHI''s packaging capacity can''t keep up with explosive AI chip orders.',
 -1, 'A supply bottleneck means sales are capped until capacity catches up.'),

(120, 120, 'supply_chain', 'Company PQR orders drop sharply as Company GHI and Company JKL delay equipment deliveries',
 'Foundries slowed capacity expansion to match soft consumer electronics demand.',
 -2, 'Delayed equipment orders ripple back up the supply chain to toolmakers.'),

(121, 121, 'supply_chain', 'Company JKL secures entire year''s supply of next-generation lithography machines',
 'An aggressive move to box out rival chipmakers.',
 0, 'One company''s gain is its rivals'' loss — little net effect on the sector.'),

(122, 122, 'supply_chain', 'Company ABC begins mass production of AI memory for Company DEF''s newest AI chips',
 'A critical design win in power efficiency and speed.',
 1, 'Design wins with the AI leader secure future revenue.'),

(123, 123, 'supply_chain', 'Company STU says AI memory chips sold out for the year, pressuring Company MNO',
 'Company MNO has failed to qualify its AI memory with Company DEF, leaving it behind.',
 1, 'Sold-out supply shows booming AI demand across the memory chain.'),

(124, 124, 'supply_chain', 'Company GHI to invest nearly $3 billion in new advanced packaging plant in Taiwan',
 'Spending to fix the severe packaging bottleneck for AI chips.',
 1, 'Fixing a bottleneck lets more AI chips ship — positive for the chain.'),

(125, 125, 'supply_chain', 'Company MNO shifts older chip capacity to focus on AI memory',
 'Repurposing equipment to capture the high-margin AI memory boom.',
 1, 'Shifting to high-margin products improves profitability.'),

(126, 126, 'supply_chain', 'Company JKL announces massive advanced packaging expansion in Malaysia',
 'An attempt to break the leading foundry''s grip on advanced chip assembly.',
 1, 'More packaging capacity eases a key bottleneck in the supply chain.'),

(127, 127, 'supply_chain', 'Company DEF unveils next-generation AI chip, securing large Company GHI capacity',
 'The new chips will strain global manufacturing and packaging chains further.',
 1, 'A strong new product means more orders flowing down the supply chain.'),

(128, 128, 'supply_chain', 'Company PQR suppliers under pressure as lead times stretch to 18 months',
 'Complex optical components from a key supplier are limiting machine output.',
 -1, 'Bottlenecks deep in the supply chain slow everyone downstream.'),

(129, 129, 'supply_chain', 'Company ABC warns of supply disruptions from China''s gallium restrictions',
 'Scrambling to find alternative sources for critical raw materials.',
 -2, 'Raw-material restrictions ripple forward through the chip supply chain.'),

(130, 130, 'supply_chain', 'Company GHI warns of potential water and power shortages in Taiwan',
 'The island''s infrastructure is straining under the demands of the newest chip factories.',
 -2, 'Concentration of factories in one place makes the sector vulnerable to local shortages.'),

(131, 131, 'supply_chain', 'Company MNO sets up dedicated AI memory supply chain task force',
 'An internal reorganization to fix yield problems and pass qualification with Company DEF.',
 0, 'An internal fix with uncertain results — little sector impact.'),

(132, 132, 'supply_chain', 'Company JKL scales back expansion plans in Vietnam amid global PC slump',
 'Halting packaging investments to save cash for its largest factory projects.',
 -1, 'Scaling back expansion signals weak demand.'),

(133, 133, 'supply_chain', 'Company DEF secures extra packaging capacity from Company KLM to bypass bottleneck',
 'Diversifying its supply chain to meet explosive AI data center demand.',
 1, 'Adding a second supplier eases a bottleneck so more chips can ship.'),

(134, 134, 'supply_chain', 'Company PQR warns reliance on single suppliers poses risk to rollout',
 'Highlights the fragility of the deep-tier chip equipment supply chain.',
 -1, 'Single points of failure make the supply chain fragile.'),

(135, 135, 'supply_chain', 'Company GHI says its most advanced node is fully booked for the year',
 'Demand from Company HIJ and Company DEF has sold out capacity, forcing other chip designers to wait.',
 1, 'Sold-out capacity shows strong demand, though others must wait.'),

(136, 136, 'supply_chain', 'Company ABC raises spending forecast to secure AI memory equipment',
 'Front-loading tool purchases to guarantee market share in the AI memory race.',
 1, 'Higher CapEx means more orders for equipment makers and confidence in demand.'),

(137, 137, 'supply_chain', 'Company EFG flags supply constraints, says Company GHI capacity a bottleneck',
 'Company GHI''s AI manufacturing capacity is fully utilized, making foundry capacity a key bottleneck.',
 -1, 'Capacity bottlenecks cap how many chips can be sold in the short term.'),

(138, 138, 'supply_chain', 'Company GHI working hard to meet chip demand, would like to raise prices',
 'AI demand is straining Company GHI and its suppliers; its Arizona expansion also faces permitting and labor constraints.',
 1, 'Pricing power from strong demand is positive, even with capacity strains.'),

(139, 139, 'supply_chain', 'Company DEF CEO says its advanced packaging needs are changing',
 'New chips shift demand toward a newer packaging technology, keeping packaging capacity critical.',
 0, 'A technical shift in supply needs — little overall sector impact.'),

(140, 140, 'supply_chain', 'Company DEF moves Company GHI capacity away from China chips as export controls stall sales',
 'Manufacturing capacity reallocated from China-focused chips toward next-generation hardware.',
 -1, 'Export controls force a company to abandon a market — a mild negative.'),

(141, 141, 'supply_chain', 'Company PQR expected to address capacity and China challenges as AI demand surges',
 'Its most advanced tools are booked for years, and supplier readiness limits expansion.',
 0, 'Strong demand offset by capacity and China risk — mixed.'),

(142, 142, 'supply_chain', 'Company PQR warns tariffs cloud outlook as equipment parts cross borders repeatedly',
 'Tariffs add cost and uncertainty because its supply chain moves components back and forth between Europe and the US.',
 -1, 'Tariffs raise costs for every border a component crosses.'),

(143, 143, 'supply_chain', 'Company GHI still evaluating next-gen lithography while Company JKL plans to use it',
 'Access to scarce next-generation tools is strategically important.',
 0, 'Strategy differences between rivals — little sector impact.'),

(144, 144, 'supply_chain', 'US finalizes chip subsidies supporting domestic advanced manufacturing',
 'CHIPS Act awards across major chipmakers aim to expand US manufacturing capacity.',
 1, 'Finalized subsidies remove uncertainty and lower expansion costs.'),

(145, 145, 'supply_chain', 'Company ABC among memory makers driving demand for lithography tools as AI capacity expands',
 'AI memory expansion is boosting orders for scarce advanced equipment.',
 1, 'Memory expansion means more equipment orders up the supply chain.'),

(146, 146, 'supply_chain', 'AI memory shortage drives long-term capacity expansion across the supply chain',
 'Industry expects memory shortages to last for years, pushing producers to expand capacity.',
 1, 'Shortages support prices and drive investment across the chain.'),

(147, 147, 'supply_chain', 'Company ABC to exit consumer memory business amid global supply shortage',
 'Shifting supply toward higher-growth AI customers as memory remains tight.',
 0, 'Shifting to higher-margin AI customers is positive for the company but tightens supply for others.'),

(148, 148, 'supply_chain', 'Company PQR denies selling advanced chipmaking tool to China after report of US concern',
 'The dispute shows how export controls decide where critical equipment can be sold.',
 -2, 'Export-control headlines threaten equipment sales — a sudden negative.'),

(149, 149, 'supply_chain', 'China''s chip tool push shows Company PQR caught in US-China squeeze',
 'China''s effort to build its own lithography tools could gradually reduce reliance on Company PQR.',
 -2, 'Losing a major market to local competitors hurts long-term sales.'),

(150, 150, 'supply_chain', 'Dutch government objects to proposed US law restricting Company PQR''s China exports',
 'The restrictions could block new shipments and maintenance for tools already installed in China.',
 -1, 'Possible new restrictions add risk — softened by the Dutch pushback.'),

(151, 151, 'supply_chain', 'Company STU to start AI chip output in Indiana, sees memory shortage lasting years',
 'An expected long-term shortage supports a tight market for AI memory.',
 1, 'A lasting shortage supports memory prices and profits.'),

(152, 152, 'supply_chain', 'Company STU expands US AI memory packaging capacity as customers drive demand',
 'Moves critical AI memory capacity closer to major US customers.',
 1, 'More capacity near customers reduces geographic risk and meets demand.'),

(153, 153, 'supply_chain', 'Export restrictions accelerate China''s push for its own lithography tools',
 'A split in the global equipment supply chain raises the value of secure access to advanced tools.',
 -1, 'Supply-chain fragmentation adds cost and long-term risk for the sector.')
ON DUPLICATE KEY UPDATE
    category = VALUES(category), headline = VALUES(headline), summary = VALUES(summary),
    impact = VALUES(impact), explanation = VALUES(explanation);
