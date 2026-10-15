-- Trading game: removes game news rewritten from articles whose sources did not exist,
-- and adds game versions of the verified replacement articles (migration 008).
-- Safe to re-run. Apply after 008. Fresh installs get this from 002_trading_game.sql.

USE stocksense;

DELETE FROM game_news WHERE id IN (7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 35, 36, 37, 38, 39, 40, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 60, 61, 63, 64, 65, 67, 68, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 82, 83, 84, 85, 86, 87, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137);

INSERT INTO game_news (id, source_article_id, category, headline, summary, impact, explanation) VALUES
(201, 201, 'geopolitical', 'US announces tighter export rules on advanced AI chips to China',
 'The US Commerce Department closed a loophole around the China-specific chips Company DEF had designed to stay within earlier limits. The company said it did not expect a near-term meaningful hit to its results.',
 -2, 'Export rules can tighten again after a workaround: a product built to comply can be shut out overnight, and the sector reprices.'),

(202, 202, 'geopolitical', 'China opens an antimonopoly investigation into Company DEF',
 'China''s market regulator opened an antitrust probe tied to an acquisition Company DEF made several years earlier. China is one of the company''s largest markets.',
 -2, 'A regulator in a key customer country can hit a supplier without any tariff: probes raise the risk of fines and lost sales.'),

(203, 203, 'geopolitical', 'Company DEF slips as the US unveils broad, tiered AI chip export rules',
 'The outgoing US administration set a tiered licensing system and a cap on computing power most countries could obtain, with a short list of close allies exempt. Critics called the rules overreaching.',
 -2, 'Sweeping rules hurt even sales to friendly markets: the wider the net, the more revenue the chip industry has to question.'),

(204, 204, 'geopolitical', 'Tech stocks drop as Company DEF and a rival warn of costs from China export controls',
 'The US told Company DEF it needed a license to export a China-market AI chip, and the company said it would take a multibillion-dollar quarterly charge. A rival chip designer warned of a hit of up to $800 million.',
 -2, 'A new license requirement can force write-downs on stock already built: the cost lands in one quarter and spreads fear to rivals.'),

(205, 205, 'geopolitical', 'Company DEF and a rival agree to pay the US government 15% of certain China chip sales, report says',
 'In exchange for export licenses, two chip designers reportedly agreed to hand the US government a share of revenue from certain chips sold in China.',
 0, 'Politics can turn a ban into a toll: a revenue share reopens sales but trims margins, so the sector reaction is mixed.'),

(206, 206, 'geopolitical', 'China says Company DEF violated its anti-monopoly law after a preliminary probe',
 'China''s market regulator said a preliminary probe found a violation tied to an earlier acquisition and that the investigation would continue. Company DEF replied that it complies with the law in all respects.',
 -1, 'Geopolitical pressure can come as legal action: a preliminary finding keeps the risk of penalties hanging over the stock.'),

(207, 207, 'geopolitical', 'US president allows Company DEF to sell a previous-generation AI chip to China for a 25% cut',
 'The US president said Company DEF could ship an older-generation AI chip to approved Chinese customers, with 25% of proceeds going to the US government. The newest chips remain excluded.',
 1, 'Policy can ease as fast as it tightens: reopened sales help, but a bigger government cut and excluded top products limit the upside.'),

(208, 208, 'geopolitical', 'US to award Company GHI billions in grants and loans to build more chip plants in Arizona',
 'The Commerce Department signed a preliminary agreement for up to $6.6 billion in grants plus about $5 billion in proposed loans. The company also announced a third fab at the site, lifting planned spending above $65 billion.',
 1, 'Government subsidies lower the cost of building fabs at home, which supports the whole chip supply chain.'),

(209, 209, 'geopolitical', 'US president says Taiwan should pay for its defense; shares of Company GHI fall',
 'The US president said Taiwan should pay the US for protection and that the island took nearly all of the US chip business. Company GHI shares fell sharply in early trading on the remarks.',
 -1, 'Remarks about Taiwan hit chip stocks fast: most advanced chips are made there, so any security doubt becomes supply-chain risk.'),

(210, 210, 'geopolitical', 'US orders Company GHI to halt shipments of advanced AI chips to China, source says',
 'A Commerce Department letter restricted shipments to China of chips made on advanced process nodes used in AI accelerators. The foundry told affected customers it was suspending such shipments.',
 -2, 'A foundry rule hits every customer at once: when the maker of advanced chips must stop shipping, the whole AI supply chain feels it.'),

(211, 211, 'geopolitical', 'What the world''s biggest chipmakers are doing to stave off the US president''s tariffs',
 'The US president proposed a 100% tariff on imported chips, but said companies building or committed to build in the US would not be charged. Company GHI''s US expansion plans were seen as a shield.',
 1, 'Tariffs often come with exemptions: companies that build in the tariffing country can win, so investment plans become a defense.'),

(212, 212, 'geopolitical', 'Company JKL faces headwinds in China as a trade body calls for a security probe',
 'A Chinese cybersecurity trade body recommended a security review of Company JKL''s products, citing vulnerabilities and failure rates. China made up about 27% of the company''s revenue the previous year.',
 -1, 'A security review is a soft ban threat: when a big share of sales sits in one country, a regulator''s doubts matter a lot.'),

(213, 213, 'geopolitical', 'Company JKL and the Commerce Department finalize a $7.9 billion chip subsidy grant',
 'Commerce awarded the company up to $7.865 billion in direct funding, below the preliminary figure. The money supports fabs and packaging in four states and is paid as milestones are completed.',
 -1, 'Subsidies are paid in stages, and the final amount can be less than the headline: markets watch the details, not just the announcement.'),

(214, 214, 'geopolitical', 'US administration reportedly weighs a 10% stake in Company JKL, making the government its top shareholder',
 'The administration was reported to be considering converting some or all of the company''s earlier chip subsidy grants into an equity stake of about 10%.',
 -1, 'Government money can come with ownership: an equity stake changes who the shareholders are and what the company must prioritize.'),

(215, 215, 'geopolitical', 'US government takes a 10% stake in Company JKL',
 'The US government invested $8.9 billion in common stock at a set price per share. Most of the money came from unpaid chip subsidy grants and the rest from a secure chips program.',
 0, 'Converting grants into shares gives the company cash without debt, but existing holders are diluted.'),

(216, 216, 'geopolitical', 'Company JKL stock is up sharply over the last month, lifting the value of the US stake to about $16 billion',
 'Company JKL shares rose strongly in the month after the US government took a roughly 10% equity stake, lifting the stake''s value well above what the government paid.',
 -1, 'A government backer can lift sentiment on a struggling chipmaker, but a big monthly run can also run ahead of the fundamentals.'),

(217, 217, 'geopolitical', 'Netherlands follows the US with export curbs on chipmaking tools',
 'The Netherlands announced license requirements for Company PQR''s most advanced immersion lithography tools after US pressure to align with its China chip-tool controls.',
 -1, 'Allies can join an export clamp: a toolmaker must now ask permission to ship its top machines, even to long-standing customers.'),

(218, 218, 'geopolitical', 'Company PQR blocked from shipping some of its critical chipmaking tools to China',
 'The company confirmed the Dutch government had partially revoked a license for two of its advanced lithography systems, following a tightening of US export controls on advanced chips and chipmaking tools.',
 -2, 'Tool bans cut off equipment makers: licenses can be revoked after the fact, so booked orders from one region may never ship.'),

(219, 219, 'geopolitical', 'Report says US will exempt allies from expanded China chip-tool restrictions',
 'Reuters reported the US would exclude allies such as Japan, the Netherlands and South Korea from an expanded rule on China chip tools, easing worries for Company PQR and other equipment makers.',
 1, 'Carve-outs matter as much as bans: news that allies are exempt can lift a toolmaker even on a day the wider sector falls.'),

(220, 220, 'geopolitical', 'China and tariffs have wiped off $130 billion from Company PQR since its peak value',
 'Company PQR''s market value fell by more than $130 billion from its peak, blamed on China export restrictions and US tariff uncertainty. It has never been able to ship its most advanced tools to China.',
 -1, 'Policy risk shows up in valuation: restrictions and tariff uncertainty can erase market value even before sales fall.'),

(221, 221, 'geopolitical', 'Company PQR comes under pressure after proposed US export curbs target an already fragile China market',
 'A bipartisan group of US lawmakers introduced a bill that would cut China off from chipmaking tools and could extend to the older machines Chinese buyers can still access. China is about 19% of the company''s system sales.',
 -1, 'Proposed laws can move a stock before they pass: a bill aimed at the machines still selling to China threatens a major revenue source.'),

(222, 222, 'geopolitical', 'China probes Company ABC for cybersecurity risks, urges Japan to stay out of US chip export curbs',
 'China''s cyberspace regulator launched a cybersecurity review of Company ABC''s products sold in China, seen as tied to US chip export curbs. The company said it was cooperating fully.',
 -2, 'Export controls invite retaliation: a security probe on a US chipmaker is a way for a country to push back without a formal tariff.'),

(223, 223, 'geopolitical', 'China bans Company ABC chips in key infrastructure over national security risks',
 'China''s cyberspace regulator said the company''s products posed serious security risks and barred operators of critical infrastructure from buying them. China was about 11% of the company''s annual revenue.',
 -1, 'A retaliation ban cuts a slice of sales: the damage is limited to the banned segment, but it shows how trade tension can reach one company.'),

(224, 224, 'geopolitical', 'US finalizes more than $6.1 billion in chip subsidies for Company ABC',
 'Commerce finalized up to $6.165 billion in funding for new memory plants in two states, backing roughly $125 billion of investment and about 20,000 jobs. The money is paid as milestones are met.',
 2, 'Domestic fab subsidies are bullish for the builder and its suppliers: lower build costs and a long-term demand pipeline.'),

(225, 225, 'geopolitical', 'Company ABC to impose a tariff-related surcharge on some products, sources say',
 'Company ABC planned a surcharge on products such as memory modules and solid-state drives, which were covered by tariffs, while the chips themselves were exempt.',
 -1, 'Tariffs can be passed to customers: a surcharge protects margins but signals higher prices and possible weaker demand.'),

(226, 226, 'geopolitical', 'Company ABC to exit server chips business in China after ban, sources say',
 'Reuters reported the company planned to stop supplying server chips to Chinese data centers after the business failed to recover from China''s earlier ban on its products in critical infrastructure. Mainland China was about 12% of last year''s revenue.',
 0, 'A ban''s damage can last: a company may give up a whole product line in a country rather than wait for it to reopen.'),

(227, 227, 'geopolitical', 'Company MNO and Company STU get indefinite waivers to ship US chip equipment to their China factories',
 'The US granted both Korean memory makers indefinite waivers to ship US-controlled chipmaking equipment to their China plants without individual licenses. China hosts roughly 40% of one maker''s NAND capacity.',
 1, 'Waivers protect production abroad: letting factories keep getting tools removes the risk of a sudden stop.'),

(228, 228, 'geopolitical', 'US to award Company MNO up to $6.6 billion in chip subsidies for its Texas expansion, Reuters reports',
 'Reuters reported the US would award the company up to $6.6 billion in subsidies for its Texas expansion. Later preliminary terms were lower, and the final award came in lower still.',
 -1, 'Early subsidy figures often shrink by the time deals close: treat first reports as a ceiling, not a promise.'),

(229, 229, 'geopolitical', 'The US president''s 100% chip tariffs hit Japan''s giants, but Company MNO and Company GHI rise on US bets',
 'Asian chip stocks swung after the US president announced roughly 100% tariffs on imported chips with exemptions for companies building in the US. South Korea''s trade envoy suggested the Korean memory makers appeared exempt.',
 0, 'Tariff headlines split winners and losers: companies that invest in the tariffing country can gain relative to those that do not.'),

(230, 230, 'geopolitical', 'US makes it harder for chipmakers to produce chips in China',
 'The US removed special trusted-user status for Korean memory plants in China and for Company GHI''s Nanjing fab. Existing facilities would be licensed to operate, but not to expand or upgrade technology.',
 -1, 'Capacity caps freeze overseas plants: a factory that can run but not upgrade slowly falls behind.'),

(231, 231, 'geopolitical', 'US approves Company MNO and Company STU chipmaking tool shipments to China under annual licenses',
 'The US granted both Korean memory makers annual licenses to bring chipmaking equipment into their China facilities, replacing the revoked trusted status. The approvals were described as temporary relief that keeps existing plants running.',
 -1, 'Short licenses mean recurring risk: relief for one year leaves renewal uncertainty hanging over the stock.'),

(301, 301, 'earnings', 'Company DEF''s revenue triples as the AI chip boom continues',
 'Data center revenue reached $14.51 billion, up 279% from a year earlier and above analyst consensus. The company guided to accelerating growth but warned that government limits on sales to some countries would hurt results.',
 1, 'A beat on expectations matters more than growth alone: the stock reacts to what was already priced in, and warnings can offset good numbers.'),

(302, 302, 'earnings', 'Company DEF sales grow 78% on AI demand, company gives strong guidance',
 'Company DEF reported revenue of $39.33 billion versus about $38.05 billion expected and adjusted EPS of 89 cents versus 84 cents. Guidance for next quarter of about $43 billion topped expectations, yet shares were flat after hours amid a Chinese AI model scare.',
 1, 'A strong beat does not always lift the stock: fears about AI spending efficiency can outweigh a good report.'),

(303, 303, 'earnings', 'Company DEF''s blowout earnings report disappoints Wall Street as the stock sinks',
 'Data center revenue jumped 75% to $62.3 billion and beat guidance, yet shares dropped after the report. Investors pointed to a stalled mega-investment deal with a major AI lab as one pain point.',
 -1, 'When expectations are sky-high, a great report can still disappoint: stocks move on the gap between results and hope.'),

(304, 304, 'earnings', 'Company DEF earnings takeaways: chief executive forecasts 70% revenue growth, far above estimates',
 'Quarterly revenue of $96.22 billion beat the $92.17 billion estimate, and the company guided to about 70% growth for the next fiscal year against the 44% analysts expected. Shares rose after hours following several post-earnings pullbacks.',
 2, 'Forward guidance can matter more than the quarter: a forecast far above estimates can break a pattern of post-earnings drops.'),

(305, 305, 'earnings', 'World''s largest chipmaker sees its biggest drop in profit in nearly five years',
 'Company GHI reported third-quarter profit of NT$211 billion, its largest decline in almost five years, as weak demand for consumer electronics persisted.',
 -1, 'The biggest foundry is a sector barometer: a sharp profit fall signals weakness for the customers who depend on it too.'),

(306, 306, 'earnings', 'Company GHI shares pop after quarterly profit soars on AI demand',
 'Net income rose 54% to NT$325.3 billion, beating the analyst estimate of NT$300.2 billion. The finance chief guided next-quarter revenue to $26.1 billion to $26.9 billion and said annual capex would be slightly above $30 billion.',
 1, 'A beat plus raised spending plans says demand is strong: capex guidance is a clue to how much equipment orders to expect.'),

(307, 307, 'earnings', 'Company GHI net profit hits a record as fourth-quarter results top expectations on robust AI chip demand',
 'Net income rose 57.0% to a record NT$374.68 billion and revenue rose 38.8% to NT$868.46 billion, driven by AI chip demand.',
 2, 'A record profit from the top foundry lifts the chain: strong AI demand there flows to equipment, memory and design firms.'),

(308, 308, 'earnings', 'Company GHI keeps its revenue forecast after profit tops estimates despite trade worries',
 'First-quarter net income rose 60.3% to NT$361.56 billion, beating estimates. The company kept its annual revenue forecast while flagging US trade policy as a risk to growth.',
 -2, 'A good beat can be overshadowed by risk: when a company only holds its forecast and flags policy, investors stay cautious.'),

(309, 309, 'earnings', 'Company GHI first-quarter profit rises 58%, beats estimates as AI demand fuels a record run',
 'Net income of NT$572.48 billion beat the NT$543.32 billion expected, and revenue narrowly topped estimates. The company guided next-quarter revenue to $39 billion to $40.2 billion, up about 10% sequentially.',
 2, 'Profit beats plus a rising revenue guide show demand is still growing, which supports sector sentiment.'),

(310, 310, 'earnings', 'Shares of Company PQR drop after new US curbs and an outlook warning',
 'Company PQR reported net bookings of 2.6 billion euros, a 42% plunge from the prior quarter as customers cut spending. It said the next year would be a transition year.',
 -2, 'Bookings are an early signal for toolmakers: a sharp drop in orders means weaker sales ahead, and the sector often follows.'),

(311, 311, 'earnings', 'Company PQR bookings surge as AI chip demand boosts purchases of its critical tools',
 'Net bookings were 5.6 billion euros, up more than 24% from a year earlier. The chief executive said AI was driving most of the industry recovery and growth.',
 1, 'Rising bookings at the toolmaker point to expansion by chipmakers, which is a forward-looking bullish sign for the industry.'),

(312, 312, 'earnings', 'Company PQR shares jump as a surge in orders defies fears of a Chinese AI model hurting chip demand',
 'Fourth-quarter net bookings were 7.09 billion euros, up 169% from the prior quarter and well above the 3.99 billion euros expected. Shares rose despite fears about AI chip demand.',
 2, 'Hard order data can calm a scare: a big bookings number is evidence that customers are still spending on capacity.'),

(313, 313, 'earnings', 'Company PQR shares drop sharply after it says it cannot confirm growth next year',
 'The company said it could not confirm that it will grow next year, and the shares fell about 11% on the news even though its bookings figure was ahead of consensus.',
 -1, 'Outlook can outweigh results: a refusal to confirm future growth can hurt more than a good order number helps.'),

(314, 314, 'earnings', 'Company PQR surges as the AI boom fuels record orders and upbeat guidance',
 'Fourth-quarter bookings came in at 13.2 billion euros, ahead of estimates and a record for orders, with EUV systems making up the majority of booking value.',
 1, 'Record orders for the most advanced tools show chipmakers planning big expansions, a bullish signal for the whole sector.'),

(315, 315, 'earnings', 'Company JKL stock rises on an earnings beat and strong revenue guidance',
 'Adjusted EPS of 41 cents beat the 22 cents expected, and revenue of $14.16 billion beat $13.53 billion. Next-quarter revenue was guided above expectations and shares rose about 7% after hours.',
 1, 'Beating low expectations and guiding up can spark a rally: both the result and the forecast count.'),

(316, 316, 'earnings', 'Company JKL shares jump on an earnings beat and uplifting guidance',
 'The company reported better-than-expected earnings and issued quarterly guidance above estimates after a quarter filled with challenges. Shares rose in extended trading.',
 1, 'After a rough stretch, even modest good news can lift a stock: low expectations make beats easier.'),

(317, 317, 'earnings', 'Company JKL issues a weak forecast, but beats on fourth-quarter results',
 'The company beat fourth-quarter expectations but issued a weak first-quarter forecast, citing seasonality, competition and an uncertain economy.',
 0, 'The forecast matters more than last quarter: a beat paired with weak guidance usually leaves the stock lower.'),

(318, 318, 'earnings', 'Company JKL plunges on soft guidance and concerns about chip production',
 'Adjusted EPS of 15 cents beat the 8 cents expected on revenue of $13.7 billion against $13.4 billion. Next-quarter guidance and a breakeven EPS forecast missed expectations, and shares fell as much as 13% on supply and yield worries.',
 -1, 'Guidance misses can overshadow a beat: weak forward numbers plus production problems can drive a sharp drop.'),

(319, 319, 'earnings', 'Company JKL soars as results top estimates, with the chipmaker showing signs of growth',
 'Revenue rose more than 7% and shares jumped 20% in after-hours trading, though the net loss widened to $4.28 billion. The chief executive said the CPU is reinserting itself as the foundation of the AI era.',
 3, 'Revenue growth can matter more than losses: a turnaround story can rally on signs of growth even before profits return.'),

(320, 320, 'earnings', 'Company ABC shares jump on an earnings beat and rosy guidance as data center revenue triples',
 'The company beat on earnings and issued guidance above estimates, with data center revenue tripling from a year earlier. Shares rose in extended trading.',
 1, 'A memory maker''s beat and guide show demand is turning: data center growth is the fastest part of its business.'),

(321, 321, 'earnings', 'Company ABC reports an earnings and revenue beat and issues a strong forecast',
 'Adjusted EPS of $1.91 beat $1.60 expected and revenue of $9.3 billion beat $8.87 billion. Fourth-quarter revenue growth of about 15% was guided, but the stock pared most of its early after-hours gain.',
 1, 'A beat does not always hold: early after-hours gains can fade when investors question how long strong numbers will last.'),

(322, 322, 'earnings', 'Company ABC pops as AI memory demand soars: ''we are more than sold out''',
 'The company said AI memory demand left it more than sold out, raised capital spending to $20 billion from $18 billion and projected a gross margin of about 68%. It expects the market for high-bandwidth memory to hit $100 billion within a few years.',
 2, 'Sold-out supply gives pricing power: when demand exceeds supply, margins rise and the whole memory segment benefits.'),

(323, 323, 'earnings', 'Company ABC soars after blockbuster earnings, lifting some chip stocks',
 'Revenue more than quadrupled from $9.3 billion a year earlier to $41.46 billion on soaring memory prices. The stock surged on the news date, lifting some other chip stocks.',
 1, 'Memory is a price-driven business: when prices rise fast, revenue and profits can multiply in a few quarters.'),

(324, 324, 'earnings', 'Company ABC beats on earnings and issues strong guidance as data center revenue jumps 11-fold',
 'Revenue of $54.23 billion beat the $51.07 billion expected and adjusted EPS of $33.42 beat $31.61. Next-quarter guidance of about $61.5 billion topped expectations, yet shares barely moved at first.',
 1, 'Even huge beats can leave a stock flat: when expectations are already very high, the news is largely priced in.'),

(325, 325, 'earnings', 'Company MNO''s profit plunges 95% to its lowest level in over a decade as chip demand slumps',
 'First-quarter profit plunged 95% as a memory chip glut hit the business. The chip unit lost 4.58 trillion won.',
 -2, 'A memory glut crushes profits: oversupply drives prices down faster than costs can be cut.'),

(326, 326, 'earnings', 'Company MNO''s semiconductor business reports a record annual loss',
 'The chip business lost 14.88 trillion won for the year, against a 23.82 trillion won profit a year earlier, with a fourth-quarter loss of 2.18 trillion won on weak global demand.',
 -1, 'Memory is cyclical: profits can swing to record losses in one year when demand weakens and inventories pile up.'),

(327, 327, 'earnings', 'Company MNO''s operating profit jumps 933% in the first quarter, beats expectations',
 'Operating profit jumped 933% and beat expectations as memory prices recovered. The chip unit returned to a 1.91 trillion won profit from a 4.58 trillion won loss a year earlier.',
 1, 'Price recovery drives the memory cycle: a return to profit shows the bottom has passed, which is bullish for the group.'),

(328, 328, 'earnings', 'Company MNO''s chip profit shrinks 40% from the previous quarter',
 'Chip operating profit fell 40% from the prior quarter to 3.86 trillion won. The company blamed inventory adjustments that hurt mobile demand despite AI and server demand.',
 -1, 'Demand is not even across end markets: weak phones can drag results even when AI and servers are strong.'),

(329, 329, 'earnings', 'Company MNO''s third-quarter profit more than doubles, beating estimates on strong demand from AI chips',
 'Third-quarter profit more than doubled and beat estimates as the chip business rebounded on AI chip demand.',
 1, 'A rebound at a memory giant shows AI demand lifting the whole memory chain, not just the AI chip designers.'),

(401, 401, 'macro', 'Tech-heavy Nasdaq has one of its best days after the Fed''s rate cut',
 'The Fed cut its benchmark rate for the first time in years and investors poured into tech stocks. Company DEF gained about 4% on the day, and lower rates and bond yields tend to benefit tech stocks.',
 2, 'Rate cuts lower the cost of capital and lift growth stocks, but sharp rallies can pause the next day as traders take profits.'),

(402, 402, 'macro', 'Company DEF and a server maker lead the AI trade carnage',
 'A disappointing jobs report stoked recession fears and worries that the Fed was behind the curve on rate cuts. Company DEF fell more than 6% in the first session of the sell-off.',
 -2, 'Weak jobs data can turn good news into recession fear: growth stocks sell hard when the economy looks like it is slowing.'),

(403, 403, 'macro', 'Stocks slide as the Fed signals it is not done hiking rates',
 'The Fed held rates steady but signaled that another hike could come, and the 2-year Treasury yield hit its highest level in about 17 years. The Nasdaq fell 1.5% that day.',
 -2, 'Higher-for-longer rates hurt growth stocks: their profits lie far in the future, so a higher discount rate cuts their value.'),

(404, 404, 'macro', 'Company DEF drops nearly 17% as a cheaper Chinese AI model sparks a global tech sell-off',
 'A cheaper AI model from a Chinese lab sparked a global tech sell-off and Company DEF had its worst day in years. The question is about the following session.',
 -3, 'A cheaper way to build AI can threaten chip demand: investors rush to reprice how much hardware the AI boom really needs.'),

(405, 405, 'macro', 'Company DEF earnings report collides with Wall Street skepticism over AI spending',
 'Ahead of Company DEF''s earnings, investors were scrutinizing cloud giants'' capital spending, with four large tech companies expected to spend nearly $700 billion combined on AI next year.',
 -1, 'Chip demand depends on customer budgets: doubts about whether AI spending can pay off weigh on the sector before the data arrive.'),

(406, 406, 'macro', 'Chip stocks shed more than $1 trillion as a sell-off hits the companies powering the AI boom',
 'Chip stocks lost more than $1 trillion in market value in about a week, with Company GHI losing $119 billion, as strategists described an unwinding of crowded AI trades.',
 -3, 'Crowded trades unwind fast: when many investors hold the same winners, a trigger can send them all for the exit at once.'),

(407, 407, 'macro', 'Asia tech stocks drop after Company EFG rattles the AI trade',
 'Disappointing results from Company EFG rattled the AI trade and dragged Asian tech shares lower, amid worries that soaring chip prices could squeeze tech margins.',
 -2, 'One big name''s miss can spread: AI chip companies are linked, so a disappointment at one weighs on the group.'),

(408, 408, 'macro', 'World''s biggest chipmaker''s sales surge 45% amid buoyant AI demand',
 'Company GHI reported July revenue of NT$467.58 billion, up 44.7% from a year earlier. Monthly sales are watched as a gauge of AI chip demand.',
 2, 'Monthly sales from the top foundry are an early read on demand: a surge is a bullish sign for AI-linked stocks.'),

(409, 409, 'macro', 'Company GHI sees August revenue surge over 53% to a record high',
 'August revenue rose 53.3% from a year earlier to NT$514.8 billion, a record and up 10.1% from July. Monthly revenue has now risen four straight months.',
 2, 'Several months of rising sales build a trend: steady monthly records suggest demand is broad, not a one-off.'),

(410, 410, 'macro', 'Memory chip giants spark a global semiconductor rally as shortages stoke price hikes',
 'Global semiconductor stocks rallied, led by Company PQR, Company GHI and Company MNO, as memory shortages stoked price hikes.',
 2, 'Tight supply lifts prices and profits, and the effect spreads from memory makers to equipment suppliers and foundries.'),

(411, 411, 'macro', 'Company PQR hits a record high on the AI boost, and analysts see plenty of room to run',
 'Company PQR shares hit a record high on the AI boom, and analysts see room to run. The company became just the third European firm valued above $500 billion.',
 2, 'A record valuation shows how much investors expect: high prices reflect hope, and the news flow has to keep up.'),

(412, 412, 'macro', 'PC sales slip for a fourth straight quarter as demand remains muted',
 'PC shipments fell for a fourth straight quarter as demand stayed muted, which affects Company JKL''s PC chip business.',
 -1, 'Consumer electronics sets the cycle for chips: a long slump in PC sales cuts demand for processors and memory.'),

(413, 413, 'macro', 'Global PC shipments drop by a third in the first quarter',
 'Global PC shipments fell by roughly a third year on year, and Company JKL''s client computing group reported $5.8 billion of revenue, down 38%.',
 -2, 'A steep drop in end-device demand flows to chipmakers: fewer PCs sold means less revenue for the processor business.'),

(414, 414, 'macro', 'US PC shipments stall as tariffs take their toll',
 'US PC shipments were flat in the third quarter amid tariffs and weak demand, as analysts said buyers pulled purchases forward earlier in the year on tariff worries.',
 -1, 'Tariffs distort demand: buyers pull purchases forward to beat the price rise, leaving a gap later.'),

(415, 415, 'macro', 'Diving DRAM prices are a problem not even AI can solve',
 'DRAM prices fell about 20% in the first quarter, with analysts expecting a further 10-15% drop the next quarter amid an inventory glut and weak demand.',
 -2, 'Memory prices drive memory profits: a glut pushes prices down, and AI demand alone may not fix an oversupplied market.'),

(416, 416, 'macro', 'Rival chip designers and Company ABC extend losses as chip stocks get clobbered',
 'The chip sell-off deepened, with several large chip designers and Company ABC extending losses and Company STU plunging, on worries over how long AI spending can keep rising.',
 -3, 'Sell-offs spread across the sector: when the AI spending story is questioned, even strong names fall together.'),

(417, 417, 'macro', 'DRAM contract prices forecast to grow only 13-18% next quarter',
 'A market research firm expects conventional DRAM contract price growth to moderate to 13-18% after up to 98% earlier in the year, citing the limited ability of PC and smartphone buyers to absorb more increases.',
 -1, 'Slower price growth is not falling prices: but when the pace of increase eases, the market may start to worry about the peak.'),

(418, 418, 'macro', 'The world''s two largest memory chipmakers signal demand weakness may have bottomed out',
 'Company MNO and Company STU signaled that the memory slump may have bottomed out, with AI-related high-bandwidth memory demand cited as a driver of recovery.',
 2, 'Memory makers talk before numbers show it: a signal that the bottom has passed can start a rally in expectation of recovery.'),

(419, 419, 'macro', 'Beware the boom and bust cycle of memory stocks, investors warn amid AI excitement',
 'Shares of the two big Korean memory makers were up 114% and 186% for the year, and investors warned of the memory sector''s history of boom and bust.',
 -1, 'After a huge run, history is a warning: memory has a cycle, and strong gains raise the risk of a sharp reversal.'),

(420, 420, 'macro', 'Company MNO and Company STU tumble over 9% as the chip rout spreads from Wall Street',
 'The two Korean memory stocks tumbled as the chip rout spread to the Korean market, where they make up over half the index. The question is about the following session.',
 -3, 'Sell-offs travel across time zones: a US tech drop can hit Asian stocks the next day, especially concentrated indexes.'),

(501, 501, 'corporate', 'Company DEF announces a $50 billion stock buyback',
 'The board approved a $50 billion share repurchase authorization alongside its quarterly earnings. The shares had surged on AI chip demand before the announcement.',
 0, 'A buyback shrinks the share count, but it does not always lift a stock that has already run up on high expectations.'),

(502, 502, 'corporate', 'Company DEF acquires an AI workload management startup for about $700M, sources say',
 'Company DEF agreed to acquire an Israeli startup that makes software for managing GPU workloads, reportedly for about $700 million. It said it would keep offering the startup''s products under the same business model.',
 1, 'Small acquisitions add software around the hardware: a modest deal like this is usually a small sector story.'),

(503, 503, 'corporate', 'Company DEF plans to invest up to $100 billion in a major AI lab as part of a data center buildout',
 'Company DEF said it plans to invest up to $100 billion as each gigawatt is deployed, under a letter of intent covering at least 10 gigawatts of its systems. Shares rose on the day of the announcement.',
 1, 'Customer financing deals secure future demand, but they can also raise questions about circular spending.'),

(504, 504, 'corporate', 'Company DEF takes a $1 billion stake in a telecom equipment maker, sending its shares sharply higher',
 'Company DEF agreed to invest $1 billion in a Finnish telecom equipment maker at a set price per share, about a 2.9% stake, alongside a partnership on AI-powered radio network products. The partner''s shares jumped about 22%.',
 1, 'A strategic stake can move the partner far more than the investor: the small company gets a big endorsement.'),

(505, 505, 'corporate', 'Company DEF buys an AI inference chip startup''s assets for about $20 billion in its largest deal on record',
 'Company DEF agreed to buy assets from an AI inference chip startup for about $20 billion in cash. The startup''s founder and president are to join, and the startup continues as a separate company.',
 0, 'Large deals buy talent and technology: the market weighs the price paid against what the acquisition adds.'),

(506, 506, 'corporate', 'World''s biggest chipmaker to open a second Japan factory with backing from Japanese corporate partners',
 'Company GHI announced a second chip factory in Japan through its venture there, with backing from Japanese corporate partners. The fab is planned for mature-to-mid-range nodes, with operations by the end of the following year.',
 2, 'Foundry expansion abroad spreads supply risk and signals long-term demand for equipment and materials.'),

(507, 507, 'corporate', 'European joint venture breaks ground on Company GHI''s first fab in Dresden',
 'A joint venture of Company GHI with European automotive and industrial chip partners broke ground on the company''s first European fab, with total investment above 10 billion euros and production targeted a few years out.',
 1, 'Building fabs close to customers adds capacity and diversifies supply, which is good for the equipment supply chain.'),

(508, 508, 'corporate', 'The US president announces a $100 billion investment in the US from Company GHI',
 'The US president and Company GHI''s chief executive announced an additional $100 billion investment in US chip manufacturing, on top of the roughly $65 billion already committed in Arizona, bringing the US total to $165 billion.',
 1, 'Big investment pledges help avoid tariffs and win goodwill: the headline number is a commitment over many years, not spending at once.'),

(509, 509, 'corporate', 'Company GHI to invest another $100 billion in Arizona after second-quarter profit soars 77%',
 'At its quarterly results, Company GHI said it will invest another $100 billion in Arizona, bringing its total there to $265 billion. The chief executive said the money would fund more advanced fabs and packaging, with no timeline given.',
 1, 'Large capex pledges signal demand but also cost: investors weigh future growth against the cash being spent.'),

(510, 510, 'corporate', 'Company PQR chief executive to retire; veteran executive to step up',
 'Company PQR said its chief executive and co-president will retire. Its Supervisory Board intends to appoint a chief business officer and 15-year company veteran as the new chief executive.',
 0, 'An orderly succession with an insider is usually a non-event: it signals continuity rather than a change in strategy.'),

(511, 511, 'corporate', 'AI startup valued at $14 billion as Company PQR takes a major stake',
 'Company PQR invested 1.3 billion euros in a French AI startup as part of a 1.7 billion euro funding round, becoming its largest shareholder with roughly 11%. The round valued the startup at about $14 billion.',
 1, 'Chip suppliers invest in the AI customers who buy their products: it deepens ties but ties up cash in a young company.'),

(512, 512, 'corporate', 'Company JKL lays off 15% of its staff and its stock tumbles as it struggles to turn around its business',
 'Company JKL announced it would cut more than 15% of its workforce, about 15,000 jobs, as part of a plan to reduce spending by $10 billion. The news came with weak quarterly results and guidance.',
 -3, 'Layoffs signal trouble: big job cuts alongside weak results suggest the turnaround is harder and slower than hoped.'),

(513, 513, 'corporate', 'Company JKL chief executive ousted by the board after disastrous performance',
 'Company JKL announced that its chief executive retired and stepped down from the board. The finance chief and the product chief were named interim co-chief executives.',
 -1, 'A sudden leadership exit shows the board''s patience ended, and it leaves uncertainty about strategy until a replacement is found.'),

(514, 514, 'corporate', 'Company JKL to sell a majority stake in its programmable chip unit for $4.46 billion to fund its revival effort',
 'Company JKL agreed to sell 51% of its programmable chip unit to a private equity firm in a deal valuing the unit at $8.75 billion, keeping a 49% stake. It expects about $4.4 billion in net cash.',
 0, 'Selling a unit raises cash and focuses the business, but gives up future profits from that unit.'),

(515, 515, 'corporate', 'Company JKL surges after a $5 billion investment from Company DEF, its best day in decades',
 'Company DEF agreed to buy $5 billion of Company JKL stock at a set price per share, about a 4% stake, and the two will co-develop data center and PC products. Shares jumped about 22% on the day of the announcement.',
 1, 'A strategic investment from a leader can lift a struggling company sharply, since it adds funding and credibility.'),

(516, 516, 'corporate', 'Company ABC to invest $3.6B in Japan for next-generation memory chips',
 'Company ABC said it plans to invest up to 500 billion yen, about $3.6 billion, in Japan to produce next-generation DRAM using EUV lithography at an existing site, with close support from the Japanese government.',
 0, 'Investment backed by a government lowers cost and shows confidence in memory demand.'),

(517, 517, 'corporate', 'Company ABC and the US administration announce expanded US investments in leading-edge DRAM manufacturing',
 'Company ABC expanded its US investment plan to about $200 billion, including a second leading-edge fab in Idaho, with up to $6.4 billion in subsidy support and an estimated 90,000 direct and indirect jobs.',
 0, 'Large investment plans show confidence in memory demand, but the cost and timeline matter to shareholders.'),

(518, 518, 'corporate', 'Company ABC to invest $24 billion in a Singapore plant as the AI boom strains global memory supply',
 'Company ABC said it will invest about $24 billion to expand wafer manufacturing in Singapore, a NAND-focused project with output expected a few years out, adding around 3,000 jobs.',
 1, 'New capacity announced during a shortage signals strong demand, but it also plants the seeds of future oversupply.'),

(519, 519, 'corporate', 'Company MNO shares climb more than 7% after surprise $7 billion buyback plan',
 'Company MNO announced a buyback of about 10 trillion won (roughly $7.2 billion) over 12 months, its first in years. Shares rose more than 7% in Seoul on the day of the announcement.',
 1, 'A surprise buyback signals management thinks the shares are cheap, and it supports the price by reducing supply.'),

(520, 520, 'corporate', 'Company MNO appoints a co-chief executive in a leadership shuffle focused on chip divisions; shares drop',
 'Company MNO named a new co-chief executive and head of its memory chip business in a leadership reshuffle focused on its chip divisions. The change came as the company struggled to keep pace with rivals in advanced memory.',
 0, 'A shake-up admits a problem: investors read a chip leadership change as a sign the business is behind rivals.'),

(521, 521, 'corporate', 'Company MNO acquires a digital health platform to merge hospital records with wearable data',
 'Company MNO agreed to acquire a digital health platform that connects patient data with clinical care. Financial terms were undisclosed, and the company aims to combine it with its wearables.',
 0, 'Acquisitions outside the core business matter little to a chip story: the deal is small next to the memory business.'),

(522, 522, 'corporate', 'Electric-vehicle maker signs a $16.5B deal with Company MNO to make AI chips',
 'Company MNO disclosed a chip supply contract worth about 22.76 trillion won running for several years, which an electric-vehicle executive confirmed was with his company. The Texas fab is to make the customer''s next AI chip.',
 1, 'A large long-term contract gives a foundry business a customer and revenue visibility, which helps its chip-making turnaround.'),

(523, 523, 'corporate', 'Company MNO builds a facility with 50,000 GPUs from Company DEF to automate chip manufacturing',
 'Company MNO and Company DEF announced a semiconductor AI factory powered by more than 50,000 GPUs, to use AI across chip design, production and equipment operations. They are also collaborating on next-generation memory.',
 1, 'Chip makers are also big buyers of AI chips: partnerships like this add demand and tie the supply chain together.'),

(601, 601, 'supply_chain', 'Company DEF reportedly delays its next-generation AI chips until next year over packaging issues',
 'Reports said Company DEF told a major cloud customer that shipments of its next-generation GPU would slip by about a quarter, linked to the complexity of advanced packaging at Company GHI. A research report pointed to a thermal mismatch that could warp the substrate.',
 -1, 'A delay moves revenue later: supply problems at a key supplier raise doubts about timing, even when demand stays strong.'),

(602, 602, 'supply_chain', 'Company DEF admits a chip defect but pledges shipments as promised',
 'Company DEF confirmed a design problem in its next-generation GPU, and the finance chief said the chip mask was changed to improve production yields. It said the ramp was still scheduled to begin the next quarter.',
 -1, 'How a company handles a defect matters: a quick fix and a held schedule can limit the damage.'),

(603, 603, 'supply_chain', 'Commentator weighs Company DEF stock after a report of its AI chips overheating',
 'A report said the company''s new AI chips overheat when connected in custom servers and that it asked suppliers to change server rack designs several times. Company DEF responded that the engineering iterations are normal and expected.',
 -1, 'Early production problems are common with new chips: the key question is whether they delay shipments to customers.'),

(604, 604, 'supply_chain', 'AI''s next bottleneck: why even the best chips made in the US take a round trip to Taiwan',
 'CNBC reported that Company DEF has reserved the majority of Company GHI''s most advanced packaging capacity, and that almost all advanced packaging happens in Asia. Company GHI is building its first US advanced packaging facilities.',
 0, 'Supply chains have bottlenecks: when one step is limited, it caps how many AI chips can ship regardless of demand.'),

(605, 605, 'supply_chain', 'Company DEF locks down memory supply from Company STU as part of a $500 billion AI deal',
 'Company DEF said it secured AI memory from Company STU in a deal that could be worth $500 billion over a number of years. The agreement includes co-development of next-generation memory and is meant to secure stable supply.',
 0, 'Long-term supply deals guarantee inputs for one buyer, but they can leave less memory for everyone else.'),

(606, 606, 'supply_chain', 'Company GHI delays the start of its Arizona chip factory',
 'Company GHI pushed back production at its Arizona plant by a year, citing a shortage of skilled workers. The fab is meant to build chips for Company HIJ.',
 -1, 'New fabs slip: labor shortages can delay capacity, which tightens supply for customers waiting on local production.'),

(607, 607, 'supply_chain', 'Company GHI warns the AI chip crunch will last another 18 months',
 'Company GHI''s chairman said the constraint on AI chips was advanced packaging capacity rather than the chips themselves. He said the company could support about 80 percent of customer needs and expected relief in about one and a half years.',
 -1, 'Supply limits can persist: a known bottleneck means AI chip demand exceeds output for a long time.'),

(608, 608, 'supply_chain', 'Company GHI evacuates fabs after a strong earthquake hits Taiwan',
 'A magnitude 7.4 earthquake struck off Taiwan''s east coast and Company GHI evacuated some fabs as a precaution. It later said there was no damage to its critical tools, including its EUV lithography tools.',
 -2, 'Natural disasters expose concentration risk: so many advanced chips are made in one place that a quake shakes the whole chain.'),

(609, 609, 'supply_chain', 'Company GHI pauses production after a strong earthquake hits Taiwan',
 'Company GHI paused work at plants after a magnitude 6.4 quake. It said structural inspections were completed at all sites and operations were gradually resuming, though tools may need recalibration.',
 -1, 'A pause is small, but recalibration can waste wafers: a short stoppage can still cost output.'),

(610, 610, 'supply_chain', 'Earthquake shook about $165M off Company GHI''s revenue forecast',
 'Company GHI warned that the earlier earthquake would cost it revenue, which a tech outlet put at roughly 165 million dollars. The disruption was small relative to its overall production.',
 0, 'Costs can be small even when headlines are scary: a quantified impact helps markets judge the real damage.'),

(611, 611, 'supply_chain', 'Company PQR ships another High-NA EUV lithography machine to an unnamed customer',
 'Company PQR shipped its second High-NA EUV lithography machine to an unnamed customer. These scanners are reported to cost roughly 300 to 400 million dollars each.',
 0, 'Each shipment of the newest tool is a milestone: it shows the next generation is moving from lab to fab.'),

(612, 612, 'supply_chain', 'Company PQR share plunge wipes over $50 billion off its value',
 'Company PQR published its quarterly results a day early with weak forecasts. It guided next-year net sales in the lower half of its prior range and bookings of 2.6 billion euros against a 5.6 billion euro consensus.',
 -2, 'A leak or early release of weak orders shocks the market: toolmaker order books show where the whole industry is headed.'),

(613, 613, 'supply_chain', 'Company PQR faces turbulence amid a stock drop and customer delays',
 'Company PQR''s stock fell after weak orders, which analysts linked to a small number of customers including Company JKL and Company MNO. China was expected to be around 20 percent of its revenue.',
 0, 'Customer concentration cuts both ways: delays at a few big buyers can drag the supplier''s orders.'),

(614, 614, 'supply_chain', 'Company JKL delays its Ohio fab build, blaming a semiconductor slowdown',
 'Company JKL postponed the completion of its planned Ohio site by years. It blamed weak semiconductor market conditions and delays in receiving subsidy money.',
 -1, 'Delayed fabs mean delayed equipment orders: a slowdown at one big builder affects the toolmakers who supply it.'),

(615, 615, 'supply_chain', 'Company JKL slows its roll on a $28B Ohio fab expansion, pushing production to the next decade',
 'Company JKL said the first Ohio fab would start production in the early part of the next decade, with the second about a year later. The company slowed construction to match capital spending with market demand.',
 -3, 'Capex discipline protects cash, but delays can signal weak demand and hurt equipment suppliers.'),

(616, 616, 'supply_chain', 'Company JKL puts consumer chip production on the back burner as data centers make a run on server chips',
 'Company JKL''s finance chief acknowledged misjudging data center demand, leading to a capacity crunch on a mature node. Capacity was shifted to server parts over client chips, so low-end PC processors could become harder to find.',
 -1, 'Capacity is limited: when a company shifts production to higher-margin products, other customers face shortages.'),

(617, 617, 'supply_chain', 'A rival gains in desktop and server CPUs while Company JKL battles a supply squeeze',
 'A rival gained CPU market share in the fourth quarter. A tech outlet tied this to Company JKL''s supply constraints on client processors after it moved manufacturing capacity toward server chips.',
 0, 'Supply shortfalls lose market share: when a company cannot ship, rivals fill the gap.'),

(618, 618, 'supply_chain', 'Company JKL begins production of its most advanced chip, inching closer to a possible deal with Company HIJ',
 'Company JKL moved an enhanced version of its newest process into early production. A newspaper had reported a preliminary deal for the company to make some chips for Company HIJ, which Company JKL had not confirmed.',
 1, 'Process milestones decide who wins foundry customers: hitting production dates helps win outside business.'),

(619, 619, 'supply_chain', 'Company ABC bounces back as AI drives up memory prices',
 'Company ABC returned to profit as AI demand lifted memory prices. Management said high-bandwidth memory was sold out for the current year, with the overwhelming majority of next year''s supply already allocated.',
 0, 'Sold-out supply with prices rising marks the turn in a memory cycle, and the benefits spread to the group.'),

(620, 620, 'supply_chain', 'Company ABC is close to selling all the high-bandwidth memory it will make next year',
 'Company ABC reported its annual results and said its high-bandwidth memory supply for next year was nearly fully committed, with pricing agreements for most of it.',
 0, 'Pre-sold supply and set pricing give visibility: revenue is partly locked in before the year starts.'),

(621, 621, 'supply_chain', 'AI memory is sold out, causing an unprecedented surge in prices',
 'High-bandwidth memory consumes so much DRAM capacity that supply for consumer electronics is being depleted. Memory makers have shifted output toward AI-focused memory, driving a broad price surge.',
 1, 'AI demand crowds out other uses: when capacity shifts to one product, prices for the rest rise too.'),

(622, 622, 'supply_chain', 'Company ABC chief executive says it cannot deliver enough memory to key customers after blowout earnings',
 'The chief executive said the company can supply only about 50 to two-thirds of key customers'' mid-term requirements. Shares fell about 3 percent the same day despite revenue nearly tripling, and the following session is the focus.',
 0, 'Shortages are bullish for pricing, but a stock can still fall: investors may fear that peak conditions are near.'),

(623, 623, 'supply_chain', 'Company MNO to make a ''meaningful'' cut to chip production to support prices',
 'Company MNO said it would make a meaningful cut to chip production as a glut hit prices. It guided first-quarter operating profit down about 96 percent after pandemic-era stockpiling left customers with excess inventory.',
 1, 'Producers cut output to lift prices: supply cuts are how a memory maker tries to end a glut.'),

(624, 624, 'supply_chain', 'Company MNO extends its memory chip production cuts and will focus on high-end AI chips instead',
 'Company MNO extended memory production cuts to normalize inventory after a memory-unit operating loss of roughly 4.36 trillion won in the quarter. It said it would focus on high-end products such as high-bandwidth memory.',
 1, 'Extended cuts show how deep a glut runs, and a pivot to premium products shows where the profit is moving.'),

(625, 625, 'supply_chain', 'Company MNO''s high-bandwidth memory reportedly fails Company DEF tests due to heat and power consumption',
 'Reuters reported that two versions of the company''s latest high-bandwidth memory failed a customer test because of heat and power problems. The company had been trying to pass the customer''s earlier tests for a long time.',
 0, 'A failed qualification blocks sales to the biggest AI buyer: passing customer tests is the gate to the market.'),

(626, 626, 'supply_chain', 'Company MNO''s 8-layer high-bandwidth memory clears Company DEF''s tests, Reuters reports',
 'Company MNO''s 8-layer memory chips passed the customer''s tests, while the 12-layer version had not yet. Supply was expected to start in the fourth quarter.',
 1, 'Passing qualification opens the door to revenue: the market rewards the first sign that a supplier can compete.'),

(627, 627, 'supply_chain', 'Company MNO expects second-quarter profits to more than halve as it struggles to capture AI demand',
 'Company MNO projected second-quarter operating profit to drop about 56 percent as it lagged rivals in supplying AI memory.',
 -1, 'Missing the AI wave hurts: a company that cannot supply the hottest product loses profit while rivals gain.')
ON DUPLICATE KEY UPDATE
    source_article_id = VALUES(source_article_id), category = VALUES(category), headline = VALUES(headline), summary = VALUES(summary),
    impact = VALUES(impact), explanation = VALUES(explanation);
