---
planStatus:
  planId: plan-business-landing-page
  title: Business landing page for saywebsolutions.com home page
  status: draft
  planType: feature
  priority: high
  owner: kyle
  stakeholders: []
  tags: [landing-page, marketing, theme, seo, research]
  created: "2026-09-11"
  updated: "2026-09-23T00:00:00.000Z"
  progress: 15
---

# Business landing page for saywebsolutions.com

## Goal

Replace the two-paragraph home page with a conversion-oriented landing page that presents Say Web Solutions' service lines (custom software, IT / self-hosted infrastructure, agentic software, AI consulting, e-commerce, email marketing, SMS marketing) to small-business buyers, backed by the 150-post technical blog as proof, with a working lead-capture path. No trackers, no external JS, strict CSP preserved.

**Success metrics**

| Metric | Target |
|---|---|
| Qualified inbound leads (form + email) | 2+/month within 90 days of launch |
| Home-page conversion (leads / unique visits, from Apache logs) | 1–3% (B2B services benchmark: IT 1.5%, dev 1.2%, consulting 1.7%) |
| Lighthouse (mobile) | ≥90 all four categories; LCP <2.5s, CLS <0.1 |
| CSP | zero violations in console |
| Reading level | ≤ grade 8 (Hemingway) above the services section |

[Landing page mockup](business-landing-page.mockup.html "width=1000 height=1600")

---

## 0. Current direction (decided 2026-09-18 to 2026-09-23)

The full ten-section landing page below (research, skeleton, mockup, contact form) is kept as reference for a later version. The first version is deliberately simpler:

- **Format:** first-person home page in plain language. Short intro, one bulleted list of what Kyle takes on, one call to action (free consultation), one paid urgent option. No marketing voice, no "practical, secure, scalable" adjective lists.
- **List order:** custom software projects first, email deliverability second (Kyle's order). The rest of the order below is a draft pending Kyle.
- **Booking (free consultation):** Nextcloud Calendar Appointments on do3 (Calendar app 6.5.2), linked from the page. Link only, no embed, no CSP change. Verified protections in the installed app: booking POST rate-limited to 10 per IP per 20 minutes (works because Redis is the distributed cache), double opt-in via a 32-character emailed token, unconfirmed bookings do not hold slots and expire after 24 hours (background cleanup job), visitor free text only reaches Kyle after confirmation. No CAPTCHA or honeypot; residual risk is backscatter confirmation mail, capped by the rate limit. Appointment config: "Free consultation", 30 min, daily maximum about 3, minimum notice 24 h.
- **Urgent paid option ($100, immediate access):** Zoho Checkout payment page with Zoho Payments as gateway (Kyle uses Zoho Payments, not Stripe). Zoho Payments' own payment links are per-customer and go inactive after one payment, so they are not suitable for a public button. Checkout page: fixed $100 one-time; name, email, phone required; custom textarea "What's going on?"; built-in CAPTCHA on; thank-you message carries Kyle's cell number and a link to a second appointment config "Urgent (paid)" (30 min, minimum notice 1 h, max 3–4 per day). Link only from the home page, no embedded button. Payment is verified in Zoho before the call; refund if the promised window is missed; deactivate the page when unavailable. Fees: 2.9% + 30¢ domestic cards, ACH 0.8% capped at $5, no monthly fee. Checkout free plan limits vary between Zoho pages (older docs: 3 pages, 50 payments); confirm when logged in.
- **Superseded by the above:** §4.2 lead-capture options A/B/C and the contact form; §7 pricing question (only the $100 urgent price appears for now).

### Draft `content/index.md` (v1)

```markdown
---
title: Say Web Solutions
description: Custom software, email deliverability, e-commerce, IT help and marketing for small businesses. Book a free consultation.
hidden: true
comments: false
---

Hi, I'm Kyle. I build software and fix computer problems for small businesses. I've been doing this since [YEAR], and most of what I learn ends up on the blog.

Here's the kind of work I take on:

- **Custom software projects.** Web apps, internal tools, and integrations between systems that don't talk to each other. I also take on older PHP and Laravel apps that nobody wants to touch.
- **Email deliverability.** If your mail is landing in spam, I'll fix SPF, DKIM and DMARC, clean up your sending setup, and get you past the Gmail and Yahoo bulk-sender rules.
- **E-commerce sites.** Shopify and WooCommerce setup, fixes, and migrations. Hooking your store up to inventory, shipping and accounting so you stop retyping orders.
- **IT help and servers.** Linux servers, backups, Nextcloud, hosting, TLS, and the everyday "why is this broken" questions. Nothing is too small.
- **Email and SMS marketing.** Campaigns, automated flows, and doing SMS the legal way: 10DLC registration, consent, opt-outs.
- **Automation and AI.** Taking repetitive work off your plate with scripts and integrations, and AI where it actually helps. I'll tell you when it doesn't.
- **Consulting.** A second opinion on a quote, a plan for a project, or someone to ask before you buy something expensive.

I'm happy to help with anything from the simplest IT question to a full custom software project. If you're not sure which of these your problem is, that's fine. Book a call and we'll figure it out together.

**[Book a free 30-minute call](BOOKING_URL)**

Or email me: kyle@saywebsolutions.com

**Need help right now?** Pay $100 and I'll call you within an hour, 8am to 8pm Central. That gets you 30 minutes of hands-on help with whatever is broken, and the $100 is credited toward any bigger job that comes out of it. If I can't reach you inside the hour, full refund.

[Get urgent help now](ZOHO_CHECKOUT_URL)
```

### Needed from Kyle before publishing

- [ ] Nextcloud appointment link (Calendar → Appointments → New → "Free consultation")
- [ ] Zoho Checkout page URL, and the urgent appointment link placed in its thank-you message
- [ ] Start year for "since [YEAR]"
- [ ] Cell number and the hours window for the urgent offer (draft says 8am–8pm Central, one hour)
- [ ] Confirm list order after the first two items, and which lines to include
- [ ] Go-ahead to write `index.md` (Nextcloud sync publishes it immediately; the page cache invalidates on the folder etag)

### v1 build steps

1. Kyle creates both appointment configs and the Checkout page.
2. Replace `content/index.md` with the draft above, placeholders filled.
3. Verify on saywebsolutions.com: links resolve, no CSP console errors, page renders from cache.
4. Later, if wanted: the fuller landing page below (spoke pages, proof posts, JSON-LD).

---

## 1. Market research summary (Sept 2026)

Four research passes: dev shops + MSPs, AI consulting + agentic, e-commerce + email + SMS, and B2B landing-page structure/benchmarks. Full source list at the bottom.

### Cross-cutting findings

1. **Small shops win on reliability + outcome + transparency, not status.** Big agencies lead with "Top 1% Shopify Plus Agency" and "$70M generated"; those claims are not credible from a one-person shop and buyers know it. The converting pattern for small shops is "you can count on us" + a concrete outcome + visible pricing (Inspry, Inspirable, Black Belt Commerce, Set.Studio).
2. **Ownership and lock-in are a top-3 pain point in every service line.** Code ownership and repo access (custom dev), vendor lock-in and app-tax creep (e-commerce), per-seat SaaS creep (IT), ESP cost creep (email), data privacy as #1 AI barrier at 33% (Bluevine 2026). This is the unifying angle available to us: everything we build or run, the client owns outright.
3. **Pricing transparency is now expected.** 74% of B2B buyers want pricing upfront; 43% eliminated vendors who required a sales call for pricing. None of 12 dev/MSP sites sampled show numbers, so "from $X" on productized offers is a differentiator.
4. **The blog is the primary trust asset.** 86% of decision-makers more likely to shortlist firms with strong thought leadership; 75% trust it over marketing (Edelman/LinkedIn 2025). Open-source work is verifiable proof (78% positive). Treat both as above-the-fold assets, not footer links.
5. **Founder-led access is the boutique differentiator.** "You talk to the person who builds it" (MartianCraft 2025 boutique vs. agency analysis; Set.Studio, Simple Thread).
6. **Named case studies with one number beat logo walls.** 42% of B2B buyers cite case studies as most influential; generic logo walls "tell procurement nothing."
7. **CTA copy: name the deliverable.** "Book a Demo" 13.1% vs "Contact Us" 4.8%; "Get My Free Audit" beats "Submit" by 30–40%; free low-friction audit/assessment outconverts calendar booking. Avoid "Get in touch", "Learn more", "Contact us to learn more".
8. **Copy mechanics:** ~500 words sweet spot, 7th-grade reading level converts 12.9% vs 6.6% (Unbounce, 57M conversions), hero must pass the 5-second test, mobile converts 40% lower so hero stays short. Forms ≤4–5 fields (each extra field costs 5–10%).
9. **AI buyers are in the "prove it" phase.** 94% fact-check AI claims; vendor collateral ranks last as a source; they want integration with existing stack (47%), measurable outcomes (32%), honesty about limits (23%). 54% of SMBs self-describe as "careful evaluators"; 45% fear "too much AI" hurts reputation. Saying "we'll tell you when AI is the wrong tool" is a positioning advantage, and silence on hallucination/privacy/lock-in is a listed vendor red flag.

### Per service line: what resonates

| Line | Winning framing (observed copy) | Top buyer pains | Avoid |
|---|---|---|---|
| Custom software | "We build and rescue web apps" (Tighten); "web applications that grow with you… not your do-it-all agency" (Laravel Company); "built around your business, not platform limitations" (Evoluted) | 45% avg cost overrun; who owns the repo; lock-in / no handover; legacy tech debt + old-PHP security; post-launch support | Tech-stack-first headlines; logo walls |
| IT / self-hosted | "Managed IT built around your business" (Ntiva); "with your budget in mind" flat fee (Cortavo); "100% open source first, data residency, 4h max response" (Linuxfabrik); Nextcloud's "own every bit of data, no per-user fees" | Slow reactive support; security (57% rank #1, 73% doubt their MSP); unpredictable cost; downtime; no transparency | Certification soup; vague "peace of mind" without a published response time |
| AI consulting | "We don't pitch AI. We build it." (Petronella); "Tell us what your business still does by hand. We build the system that does it." (Epiphany); "roadmap + working proof of the top idea; scope and fee agreed before kickoff" (Prometheus) | Privacy #1 (33%); accuracy distrust (31%); "where do I start" (73% want implementation help); 82% of <5-person firms think AI isn't applicable to them | "transform", "AI-powered", "unleash/elevate/revolutionize", "intelligent solutions", "not just X but Y", FAANG logo walls |
| Agentic software | "AI agents that run in production… one real workflow… a system your team owns" (Vstorm); "AI that ships" (Tribe); "The LLM is the easy part. The plumbing is the job." | Sandbox demos with fake data; no evals/audit trail; model churn | Gartner-stat heroes; "streamline operations at scale" |
| E-commerce | "the agency you can count on" (Inspry); "care plans that keep your store selling… staging-tested updates that never break checkout" (Inspirable); "zero downtime, SEO-safe migration, 301s on day one" (IWD) | Systems that don't talk (inventory/ESP/CRM); overselling from bad integrations; app-tax creep; migrations losing 30–50% organic; checkout breakage | "$XXM revenue generated" |
| Email marketing | "Your emails belong in the inbox" / "63% of SPF records are silently broken" (DeliverPeak); "flows drive 41% of email revenue from 5% of sends" (Klaviyo 2026) | Spam-foldering after Gmail/Yahoo/Microsoft bulk-sender rules (SPF+DKIM+DMARC, one-click unsubscribe, <0.3% spam rate); ESP cost creep ($150–300/mo hosted vs $10–40 self-hosted); only one abandoned-cart email ever built | "$72 per $1", "4200% ROI", revenue-share pricing |
| SMS marketing | Compliance as the value prop: "the gold standard in compliance… protection from costly lawsuits" (Postscript) | TCPA class actions up 283% (1,636 YTD 2025, $500–1,500/violation); carriers block unregistered A2P since Feb 2025; 10DLC registration friction; state mini-TCPAs | "98% open rate" (unsourced) |

### Productized offer patterns worth copying

| Offer | Market anchor | Our fit |
|---|---|---|
| Free 30-min assessment → written plan + fixed-price quote | Universal (Epiphany, Layer3, Vstorm) | Primary CTA for all lines |
| "One workflow" AI pilot, fixed price, 2–4 weeks, client owns it | $3–15k single-workflow; $10–35k pilot | Strong: private-by-default, self-hosted or API with no-training terms |
| Self-hosted IT care plan, flat monthly, published response time | MSP flat fee; Inspry $199–299/mo care plans | Strong: Nextcloud + email + backups + TLS + monitoring |
| Compliant SMS setup (10DLC/TCR registration, consent capture, STOP/HELP) | Agencies bundle SMS at $5k+ minimums; standalone compliant setup is an open niche | Strong: technical, fixed fee, low competition |
| Inbox check: SPF/DKIM/DMARC fix + ESP setup | Deliverability audits $3–8k, or free assessment + hourly | Strong: existing Postfix/DKIM/SPF writing |
| Store care plan / integration fixes | $195–600/mo market | Medium |
| Store or email audit, fee credited to retainer | $500–997, 100-point, 90-day guarantee | Optional |

---

## 2. Positioning (recommended default; see Open Questions)

**Umbrella:** practical software, private infrastructure, and AI automation for small businesses, built and run by the person you talk to, and owned outright by you.

**Why "ownership" as the spine:** it is the one theme that is a top-3 pain in all seven lines (see finding 2), it is true of how we already work (self-hosted Nextcloud, self-hosted comments, open-source CMS, this site runs on our own stack), and it is what none of the sampled competitors lead with.

**Three buckets** (hub-and-spoke: home summarises, each bucket gets a spoke page in phase 2):

- **Build** — custom web apps (Laravel/PHP), integrations and API glue, legacy rescue, e-commerce integrations and migrations, AI agents and agentic software (MCP servers, tool-using LLM workflows, RAG over your documents). *Production first; you own the repo from day one.*
- **Run** — self-hosted IT: Nextcloud, Linux servers, email (SPF/DKIM/DMARC), backups, TLS, monitoring, hardening. *Flat monthly, published response time, your data on servers you control.*
- **Grow** — email marketing (deliverability, flows, self-hosted ESP option), SMS marketing (10DLC + TCPA-compliant setup, cart recovery), AI consulting (readiness audit, workflow mapping, one-page AI-use policy, and when *not* to use AI).

**Primary CTA:** "Get a free 30-minute assessment" (anchors to the contact form). **Secondary:** visible email address as plain text link; optional booking link.

---

## 3. Page skeleton and draft copy

Target ≈500 words above the writing section. Single H1. Draft copy below is a starting point for Kyle to edit; bracketed items need real values.

### 3.1 Hero

- **H1 (default):** *Custom software, private IT, and AI automation you actually own.*
- **H1 alternates:** *Software that does the boring work. Systems you own outright.* / *Practical software for small businesses, built by the person you talk to.*
- **Subhead:** *Say Web Solutions builds and runs practical systems for small businesses: web apps, self-hosted infrastructure, e-commerce plumbing, and AI that handles the work your team shouldn't do by hand. No account managers, no lock-in.*
- **CTA:** [Get a free 30-minute assessment] · or email [kyle@…]
- **Micro-proof line under CTA:** *Shipping web software since [YEAR] · 150 technical articles · Open-source maintainer (CMS Pico for Nextcloud)*

### 3.2 Who we help (3 cards)

1. **Owners drowning in manual work** — spreadsheets, copy-paste between systems, reports assembled by hand. We automate it, with or without AI.
2. **Stores whose systems don't talk** — inventory, email, CRM and checkout out of sync; carts abandoned; migrations that lose rankings.
3. **Teams who want off Big SaaS** — your files, email and data on servers you control, for a flat monthly cost.

### 3.3 Services (3 cards → spoke pages)

- **Build** — one-line promise + 4 sub-bullets (custom apps · integrations & rescue · e-commerce · AI agents). Footer line: *You own the code and the repo from day one.*
- **Run** — one-line promise + 4 sub-bullets (Nextcloud & files · Linux servers & backups · email that lands · security & TLS). Footer line: *Flat monthly. [X]-hour response, in writing.*
- **Grow** — one-line promise + 3 sub-bullets (email flows & deliverability · compliant SMS · AI readiness & policy). Footer line: *We'll tell you when AI is the wrong tool.*

### 3.4 Proof

- **Case study (own, real numbers):** *Moved saywebsolutions.com to self-hosted Nextcloud + Pico: 469 URLs, zero broken links, zero downtime, cached pages in 0.13s, self-hosted comments with no tracking.* Link to the write-up (new blog post; see content tasks).
- **Case study 2:** [client work, problem → what we built → one number]. Needs Kyle.
- **Open source:** CMS Pico for Nextcloud modernization (Nextcloud 30+, PHP 8.2+), GitHub link.
- **Testimonial:** one attributed quote (name, role, company) placed next to a CTA. Needs Kyle.

### 3.5 Process (3 steps)

1. **Free 30-minute call** — what you do by hand, what breaks, what it costs you.
2. **Written plan and fixed price** — scope and fee agreed before kickoff.
3. **Build in short blocks** — working software every one to two weeks; you get the repo, the docs and the keys.

### 3.6 Founder

Photo (AVIF/WebP, ~400px) + 3 sentences: who Kyle is, since when, "when you hire Say Web Solutions you get me, not a handoff", and the honesty line: *I'll tell you when custom software or AI is the wrong answer.*

### 3.7 Writing (3 featured posts + GitHub)

Hand-picked, one per bucket, set in front matter:

- Build → `creating-a-new-web-app-mobile-project-the-whole-process` (or the CMS Pico modernization post once written)
- Run → `migrating-a-nextcloud-instance-to-new-server` or `send-outbound-email-postfix-dkim-spf-ubuntu-16-04`
- Grow/AI → currently thin (`my-cursor-ide-rulse-for-ai-prompt`, `add-claude-ai-kde-web-shortcuts`). See content tasks: 2–3 new posts needed for AI/SMS credibility.

### 3.8 Final CTA + form

Heading: *What does your business still do by hand?*
Form (4 visible fields): name · email · what do you need (textarea) · optional budget range (select: <$2k / $2–10k / $10k+ / monthly retainer). Hidden: honeypot + render timestamp. Below the form: plain email link. Button: *Get my free assessment*.

### 3.9 Pricing lines (if approved; see Open Questions)

"From" pricing on the productized offers only: IT care plan *from $[X]/mo*, compliant SMS setup *$[X] fixed*, inbox check *$[X] fixed*, one-workflow AI pilot *from $[X]*. Custom builds: *typical projects $[low]–$[high]; fixed price after the free assessment.*

---

## 4. Technical design

### 4.1 Content and template

- `content/index.md` — keep `hidden: true`, `comments: false`; add `template: landing`; move all structured copy (hero, audiences, services, process, featured post ids, CTA labels) into YAML front matter so Kyle edits copy in Nextcloud without a theme deploy. Markdown body = founder paragraph.
- `appdata/themes/default/landing.twig` — extends `base.twig`; renders the front-matter structures; featured posts resolved via `pages("blog")` matched on id; no JavaScript at all.
- `base.twig` — add `{% block body_class %}` (landing needs a wider container than the 800px blog column), a `<link rel="canonical">` (already on the cutover list; do it here), and a `{% block schema %}` for JSON-LD.
- `partials/nav.twig` — Home · Services (`/#services`, later `/services`) · Blog · Contact (`/#contact`).
- `style.css` — `.landing` styles: hero, 3-column card grids collapsing to 1 column ≤640px, process steps, founder block, form, focus states. Fix footer `#999` (2.85:1, fails AA) → `#6b6b6b`. No new fonts.
- New content page `content/thanks.md` (`robots: noindex`, `hidden: true`) as the post-submit redirect target.

### 4.2 Lead capture (decision needed; see Open Questions)

Live CSP today: `default-src 'none'; script-src nonce + comments origin; style-src 'self' 'unsafe-inline'; img-src 'self' blob:; connect-src 'self' + comments origin; frame-src 'self' + comments origin; frame-ancestors 'self'`. No `form-action` directive, so plain HTML form POST to any origin works today; `connect-src 'self'` blocks fetch-based submission to third parties; `frame-src` blocks Calendly/Cal.com embeds (a link works).

| Option | How | Pros | Cons |
|---|---|---|---|
| **A. Same-origin handler in cms_pico (recommended)** | New `ContactController`: `#[PublicPage] #[NoCSRFRequired] #[AnonRateLimit(limit: 5, period: 3600)]` POST route `/pico_contact/{site}` plus matching `pico_proxy` route for the custom domain; validates, honeypot + ≥3s timestamp, sends via Nextcloud `IMailer` to `occ config:app:set cms_pico contact_email`; 303 → `/thanks` | No third party; consistent with "self-hosted" story; works with page cache (no per-request token); brute-force protection built in | ~half a day of PHP + tests; must handle custom-domain routing |
| B. Web3Forms | `<form action="https://api.web3forms.com/submit" method="POST">` + hidden `access_key` + `redirect` | Zero backend; 250/mo free; free redirect | Third party sees leads; if the CSP-hardening branch adds `form-action 'self'`, must also allow `https://api.web3forms.com` |
| C. mailto + booking link | No form | Zero work | Fails without a mail client; no spam control; measurably lower conversion |

Either A or B: page cache renders the form once per etag, so nothing per-request may appear in the form except the render timestamp (acceptable: the ≥3s check is a floor, not an exact match).

### 4.3 SEO and structured data

- `<title>`: *Custom Software & IT for Small Business | Say Web Solutions* (≤60 chars). Meta description 140–160 chars from `description` front matter.
- JSON-LD `Organization` in `landing.twig` (`name`, `url`, `logo` ≥112px, `description`, `founder`, `sameAs` [GitHub, LinkedIn], `contactPoint`, `areaServed`, `hasOfferCatalog` → one `Service` per line). Use `ProfessionalService`/`LocalBusiness` only if a real service address is published.
- Canonical link in `base.twig` claiming `https://saywebsolutions.com/...` for every page.
- Spoke pages (phase 2): `content/services/{build,run,grow}.md`, each with its own H1, keyword, `Service` JSON-LD with `provider` → the Organization `@id`, and CTA.

### 4.4 Performance and accessibility

- Headshot: `<picture>` AVIF → WebP → JPEG, explicit `width`/`height`, `fetchpriority="high"`, `<link rel="preload">`; everything below the fold `loading="lazy"`.
- No external assets (CSP already forbids); no JS on the landing page.
- WCAG 2.2 AA: one H1, 4.5:1 contrast, visible focus, 24px targets, labels on every field, skip link, `prefers-reduced-motion` guard on any transition.

### 4.5 Deploy and staging (no staging environment exists)

1. Build on a new branch `feature/landing-page` off `master` (the current branch carries unrelated uncommitted CSP work).
2. First deploy the template and publish the copy as a **hidden page** `content/landing.md` with `template: landing`; review at `https://saywebsolutions.com/landing` in production without touching the home page.
3. When approved, move the front matter into `content/index.md`, delete `landing.md`, warm the cache, run Lighthouse + axe, confirm zero CSP console errors, submit the home URL in Search Console.
4. Rollback = restore the previous `index.md`. The page-cache key includes the website folder etag plus theme/plugin/app-setting state, so content edits and theme deploys both invalidate automatically; kill switch remains `occ config:app:set cms_pico page_cache --value=0`.

---

## 5. Content Kyle needs to provide

- [ ] Founded year / "shipping since" year
- [ ] Headshot (min 800px source)
- [ ] One attributed testimonial (name, role, company, permission)
- [ ] One client case study with a single number (or approve using only the own-site migration)
- [ ] Which of the seven lines are live offers today vs. aspirational (see Open Questions)
- [ ] Published response time for the IT care plan (e.g. "next business day" or "4 hours")
- [ ] Pricing decision and the actual "from" numbers
- [ ] Contact email to publish; booking link if any (Cal.com/Calendly public page)
- [ ] GitHub and LinkedIn URLs for `sameAs`
- [ ] Service area (city/region/remote-only) for `areaServed`

## 6. Content tasks (blog posts that double as proof)

- [ ] "How we migrated a 469-URL site to self-hosted Nextcloud with zero broken links" (Run proof; write-up of `CUTOVER.md`)
- [ ] "Building a Redis page cache for Pico CMS on Nextcloud" (Build proof)
- [ ] One agentic post: an MCP server or tool-using workflow with evals and an audit trail (Build/AI proof)
- [ ] "10DLC registration and TCPA consent capture for a small store, step by step" (Grow/SMS proof)
- [ ] Refresh the Postfix/DKIM/SPF post for the 2024+ Gmail/Yahoo/Microsoft bulk-sender rules (Grow/email proof)

---

## 7. Open questions (to decide before build)

1. **Positioning spine:** ownership ("you actually own") vs. practicality ("does the boring work") vs. founder-led ("built by the person you talk to")? Default: ownership headline, founder-led subhead.
2. **Service grouping:** Build / Run / Grow buckets vs. by buyer type vs. flat list of seven? Default: Build/Run/Grow.
3. **Lead capture:** A same-origin handler, B Web3Forms, or C mailto + booking link? Default: A.
4. **Pricing:** publish "from" prices on productized offers, publish full packages, or none? Default: "from" on productized offers only.
5. **Which lines are primary today?** Research suggests leading with what has proof (custom software, self-hosted IT, email deliverability) and listing AI/agentic/SMS/e-commerce as capabilities until posts and a case study exist.
6. **Location targeting:** remote-only (`Organization` + `areaServed`) or local leads (`LocalBusiness` with address)? Default: remote-only, no city.
7. **Booking link:** add a Cal.com/Calendly public page as secondary CTA? Default: email only until a booking page exists.

---

## 8. Implementation tasks

- [ ] Decisions on §7 recorded here
- [ ] Gather §5 content
- [ ] Branch `feature/landing-page` from `master`
- [ ] `base.twig`: `body_class` block, canonical link, `schema` block
- [ ] `landing.twig` + `.landing` CSS (mobile-first, no JS)
- [ ] Contact handler (option A) or Web3Forms wiring (option B) + `thanks.md`
- [ ] `nav.twig`: Services + Contact
- [ ] Front-matter copy in `content/landing.md` (hidden) for production review
- [ ] Headshot exported to AVIF/WebP/JPEG in `assets/images/`
- [ ] JSON-LD `Organization` + `hasOfferCatalog`
- [ ] Footer contrast fix
- [ ] Deploy per `CLAUDE.md`, review `/landing`, Lighthouse + axe + CSP console
- [ ] Swap into `index.md`, warm cache, Search Console
- [ ] Phase 2: spoke pages `content/services/*.md` with `Service` JSON-LD
- [ ] Phase 2: §6 proof posts, then update featured posts in front matter
- [ ] 30/60/90-day review of lead count and Apache-log conversion rate

---

## 9. Sources

**Dev shops / MSPs:** tighten.com · kirschbaumdevelopment.com · laravelcompany.com · luckymedia.dev · steadfastcollective.com · evoluted.net · ntiva.com · nexigen.com · dataprise.com · cortavo.com · redkeysolutions.com · linuxfabrik.ch · nextcloud.com/enterprise · prontomarketing.com/blog/best-msp-websites · connectwise.com (SMB cybersecurity stats) · techaisle.com (2025 SMB security survey) · serenitllc.com/blog/msp-sla-guide · pragmaticcoders.com (vendor lock-in, vendor questions) · eltexsoft.com (cost overruns) · news.designrush.com/b2b-pricing-transparency · taskip.net (retainer vs project) · predictableprofits.com (B2B CRO benchmarks 2025)

**AI / agentic:** petronellatech.com · epiphanydynamics.ai · layer3labs.io · prometheusagency.co · vstorm.co · tribe.ai · ae.studio · parlance-labs.com · winder.ai · bluevine.com (SMB AI trends 2026, n=942) · goldmansachs.com (small-business AI survey 2026, n=1,256) · business.com (AI usage SMB study, n=1,009) · simplybusiness.com (2026 outlook, n=1,047) · hginsights.com (TrustRadius 2026 B2B buying disconnect, n=1,862) · infuse.com (voice of the buyer, AI) · aismartventures.com (vetting AI consultants) · plainpath.ai (AI vendor red flags) · buildwithdew.com (productized AI offers 2026) · digitalapplied.com (AI agency pricing 2026) · wisernotify.com (CTA stats) · contentbeta.com (overused AI words)

**E-commerce / email / SMS:** inspry.com · inspirable.com · neversettle.it · iwdagency.com · blackbeltcommerce.com · flowium.com · charleagency.com · deliverpeak.com · dmarctrust.com · postscript.io · stickydigital.io · attentive.com · baymard.com/lists/cart-abandonment-rate · support.google.com/a/answer/81126 (bulk sender rules) · valebyte.com (self-hosted Mautic/Listmonk) · litmus.com (email ROI) · klaviyo.com (2026 benchmarks; abandoned-cart benchmarks) · omnisend.com (2026 statistics) · theemailmarketers.com (agency pricing) · smartlead.ai (deliverability consultants) · natlawreview.com (TCPA class actions Sept 2025) · beancount.io (10DLC/TCPA 2026 guide) · womblebonddickinson.com (one-to-one consent rule vacated) · unbounce.com (professional-services conversion benchmark) · saashero.net (CTA practices)

**Structure / benchmarks / tech:** testdouble.com · thoughtbot.com · set.studio · simplethread.com · cloudfour.com · unbounce.com (B2B conversion rates) · foundrycro.com (2026 benchmarks) · firstpagesage.com (B2B landing page conversion rates 2026) · instapage.com (B2B landing page practices) · genesysgrowth.com (B2B homepages) · blendb2b.com · melisaliberman.com (consulting site examples) · kingcontentagency.com (headline formulas) · thecompleteapproach.substack.com (stop asking for demos) · martiancraft.com/blog/2025/08/big-agencies-vs-boutiquefirms · developer.mozilla.org (CSP form-action) · splitforms.com (Formspree vs Web3Forms) · usebasin.com/pricing · help.formspree.io (redirects) · cal.com/docs (embed) · developers.google.com (Organization structured data) · schema.biz (Service) · corewebvitals.io · logoswebdesigns.com (image optimization 2026) · levelaccess.com (WCAG 2.2 AA checklist) · revenuezen.com (titles/meta) · edmondscommerce.co.uk (load-time impact)
