# saywebsolutions.com Cutover Runbook

Goal: move saywebsolutions.com from the old Fresh/Deno site (34.120.54.55) to the
Pico site on do3 (161.35.234.38) with effectively zero downtime and zero broken URLs.

**Why downtime is near-zero by design:** the old site keeps serving until the DNS
flip; the new site is fully operational on the same URL structure *before* the flip
(verified via `curl --resolve`, which pins the domain to the new IP without touching
DNS). The flip itself only changes where resolvers point. Rollback is the same
operation in reverse.

Current state (verified):

- All 469 old-site URLs serve correctly through the new vhost (200s, no redirects)
- Page cache active: ~0.13s/page cached, 469 URLs @ 12 concurrent = 6.5s, 0 errors
- Apex A record: 34.120.54.55, TTL 3600, DNS hosted at DigitalOcean
- No `www` record exists (old site never had one)
- Vhost: `/etc/apache2/sites-available/saywebsolutions.com.conf` (port 80 only so far)

---

## Phase 0 — Prerequisites (need from Kyle)

- [x] **DigitalOcean API token** (scoped: read/write on DNS) — stored on do3 at
      `/root/.secrets/certbot-dns-digitalocean.ini` (chmod 600).
- [ ] Go/no-go for a cutover window. Any low-traffic time works; nothing here is
      time-critical once prepared.

## Phase 1 — Prep (T-1 day, no user-visible impact) — DONE 2026-07-13

- [x] **Lower apex TTL 3600 → 300** in DO DNS — done for the A record **and the
      AAAA record** (surprise finding: `@ AAAA 2600:1901:0:6d85::` points IPv6
      clients at the old Google Cloud site; must be **deleted at flip**, do3 has
      no IPv6).
- [x] **Free disk space on do3**: journal vacuumed (840 MB freed), 6.4 GB free.
- [x] **Deleted stale `_acme-challenge` CNAME** (→ deno.dev) — it blocked DNS-01
      (CNAME can't coexist with the validation TXT). Old site keeps its current
      cert until expiry; only its renewal breaks, and it's being decommissioned.
- [x] **Pre-issue TLS certificate via DNS-01**: cert for saywebsolutions.com +
      www.saywebsolutions.com issued (expanded a pre-existing apex-only cert),
      expires 2026-10-11, auto-renews via the DO DNS plugin.
- [x] **Build the :443 vhost** — done; also found and disabled stale Laravel-era
      vhosts (`sws.conf`, `sws-le-ssl.conf`) that claimed the domain on :80/:443.
      New config: :80 → HTTPS redirect, www:443 → apex redirect, apex:443 full
      proxy config with cert. Logs: `saywebsolutions-{access,error}.log`.
- [x] **Pre-flip HTTPS verification**: all 470 URLs return 200 over :443 via
      `--resolve` (146 posts, 319 tags, 4 index pages, /blog/search). Images,
      `/blog/tags/.ppk`, http→https and www→apex redirects verified.
- [x] **Rename website**: "Blog" → "Say Web Solutions" (via DB; title renders).
- [x] **Warm the page cache**: 3 passes; final sweep 470/470 `X-Pico-Cache: HIT`
      in 22 s at concurrency 12.

## Phase 2 — Comments remap (T-0, minutes before flip)

Remark42 comments are keyed to full page URLs (currently
`https://nc.saywebsolutions.com/apps/cms_pico/pico/web/blog/<slug>`). After the
flip, pages report `https://saywebsolutions.com/blog/<slug>`.

- [x] Remap rules file staged at `/var/www/nextcloud/data/remark42/remap_rules.txt`
      (single prefix rule: `https://nc.saywebsolutions.com/apps/cms_pico/pico/web
      https://saywebsolutions.com`).
- [x] Remap executed — 45 comments moved. Two gotchas learned: `remark42 remap`
      talks to the **running** server's admin API (no service stop needed), and
      rules need trailing `*` wildcards for prefix matching (first run without
      them was a silent no-op; verified by counting comments on old + new URLs
      before/after).

## Phase 3 — Flip (T-0)

Executed 2026-07-13 15:26.

- [x] Apex A record → `161.35.234.38`; propagated in ~5 minutes.
- [x] Apex AAAA record deleted (`2600:1901:0:6d85::`, old Google Cloud IPv6).
- [x] `www` A record added → same IP (redirects to apex).

## Phase 4 — Verify (T+10 min) — DONE 2026-07-13

- [x] All 470 URLs over real DNS: 200 + `X-Pico-Cache: HIT`.
- [x] Browser check: post renders, comments widget loads cross-origin from
      `nc.saywebsolutions.com/comments`, imported Disqus comment displays,
      comment form present — CSP extension works in situ.
- [x] `http://` → `https://` 301; `www.` → apex 301; cert CN correct,
      expires 2026-10-11.
- [x] Logs clean (no Apache errors, 0 NC errors); Googlebot already crawling
      through the new server minutes after the flip.

**Rollback (any point, ≤5 min):** revert A record to `34.120.54.55`. Old site is
untouched throughout; nothing else needs undoing. If only comments misbehave:
re-run remap in reverse or restore from remark42's daily backup — no DNS action.

## Phase 5 — Post-cutover (T+1 day and later)

- [ ] Raise TTL back to 3600 once stable.
- [ ] Canonical `<link>` in theme claiming `https://saywebsolutions.com/...`
      (site remains reachable via the `nc.` URL; canonical prevents split indexing).
- [ ] Google Search Console: verify domain, watch crawl stats/errors.
- [ ] After ~2 weeks stable: decommission the old Deno Deploy project.
- [ ] Delete this file once complete; durable notes live in CLAUDE.md.
