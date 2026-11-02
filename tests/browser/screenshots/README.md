# README screenshots

`readme.shot.ts` writes the PNGs in `docs/images/` that the module README shows. It is not a test
suite: it runs with its own config, `playwright.screenshots.config.ts`, which the browser tests and
CI never load, so a normal test run never rewrites the images. Never edit the PNGs by hand;
regenerate them.

What it needs:

- a Silverstripe 6 host with this module installed and the browser fixtures from
  `tests/browser/fixtures/` copied into its `app/src/`, then the database built. `FiBShowcasePage`
  seeds the content on every build: a published `/featured-showcase` page with three images
  (maximum 3) and `/featured-showcase-single` with one (the default maximum of 1). The images are
  simple landscapes drawn with GD, so the host's PHP needs `ext-gd`;
- `BROWSER_TARGET_URLS` pointing at that host (only the `ss6` target is used, else the first).

The shared runner in the maintainers' workspace does all of that:

```bash
BROWSER_ONLY=ss6 tools/browser/run.sh silverstripe-featuredimages --config=playwright.screenshots.config.ts
```

Against a host you started yourself, from `tests/browser/`:

```bash
npm ci
BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8874" npx playwright test --config=playwright.screenshots.config.ts
```

The browser specs' own pages ("Featured multi", "Featured publish", "Featured single") are hidden
from the site tree in the screenshots; nothing else is changed.
