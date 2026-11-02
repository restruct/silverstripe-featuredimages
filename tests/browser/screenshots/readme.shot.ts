import { test, expect, type Page } from '@playwright/test';
import * as path from 'path';

// Generates the README screenshots in docs/images/. Not a test suite: run it with
// playwright.screenshots.config.ts (see README.md next to this file). The content comes from the
// browser fixtures: FiBShowcasePage seeds /featured-showcase with three images (max 3) and
// FiBShowcaseSinglePage seeds /featured-showcase-single with one (the default of 1).

const IMAGES = path.join(__dirname, '..', '..', '..', 'docs', 'images');

/** Tree labels of the pages only the test host has (the browser specs' own pages). */
const FIXTURE_PAGES = ['Featured multi', 'Featured publish', 'Featured single'];

/**
 * Hide the browser specs' pages from the site tree. Presentation only; nothing about the module's
 * own UI is changed. CSS cannot match on text, so the tree nodes are hidden by their label.
 */
async function hideFixturePages(page: Page): Promise<void> {
    await page.evaluate((labels) => {
        document.querySelectorAll<HTMLElement>('.cms-tree li').forEach((li) => {
            const label = li.querySelector(':scope > a')?.textContent?.trim() ?? '';
            if (labels.includes(label)) {
                li.style.display = 'none';
            }
        });
    }, FIXTURE_PAGES);
}

/** Wait until every <img> on the page has loaded (the upload field's thumbnails are <img>s). */
async function imagesLoaded(page: Page): Promise<void> {
    await expect
        .poll(() => page.evaluate(() => Array.from(document.images).every((i) => i.complete && i.naturalWidth > 0)), {
            message: 'all images loaded',
        })
        .toBe(true);
}

/**
 * Open a page in the CMS by its tree label and screenshot the top of the window down to just
 * below the "Page Image(s)" field, with the Content label under it to show where the field is
 * placed (the editor itself adds nothing).
 */
async function shootEditForm(page: Page, title: string, items: number, file: string): Promise<void> {
    await page.goto('/admin/pages');
    await page.locator('.cms-tree a').filter({ hasText: title }).first().click();
    const field = page.locator('#Form_EditForm_FeaturedImages_Holder');
    await expect(field.locator('.uploadfield-item')).toHaveCount(items);
    await hideFixturePages(page);
    await imagesLoaded(page);
    // No hover or focus highlight from the click into the tree.
    await page.mouse.move(5, 795);
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    await page.waitForTimeout(500);

    const box = (await field.boundingBox())!;
    await page.screenshot({
        path: path.join(IMAGES, file),
        clip: { x: 0, y: 0, width: 1280, height: Math.min(800, Math.ceil(box.y + box.height + 38)) },
    });
}

test('Page editor: three sortable featured images', async ({ page }) => {
    await shootEditForm(page, 'Summer walks', 3, 'page-images-multiple.png');
});

test('Page editor: the default of one featured image', async ({ page }) => {
    await shootEditForm(page, 'Our team', 1, 'page-image-single.png');
});
