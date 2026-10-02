import { test as base, expect, type Locator, type Page, type Request } from '@playwright/test';

// Shared fixtures and helpers for the featuredimages specs.
//
// The pages are seeded on every dev/build by tests/browser/fixtures/ (copied into the scratch host
// by the runner): FiBPage (the extension with max_featured_images 3) at /fib-multi and
// /fib-publish, FiBSinglePage (the default of 1) at /fib-single. Their controller renders the
// page's $PageImage and $PageImages as bare HTML, plus a link to the page's CMS edit form.

/**
 * test, extended with an automatic console guard: every spec fails if the page logs a console
 * error or throws an uncaught exception at any point, page load included. "Failed to load
 * resource" (any 4xx/5xx asset or request) arrives as a console error too, so a failed upload or
 * a missing image is caught here as well. Warnings (the admin's own Apollo deprecation notices)
 * do not count.
 */
export const test = base.extend<{ consoleGuard: void }>({
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message}\n${err.stack}`));

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors or uncaught exceptions').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** A 1x1 PNG. Every upload gets its own file name, so the content may repeat. */
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
    'base64',
);

/**
 * A file name unique to this run and repeat, so files from earlier runs (the asset store keeps
 * them) never stand in for this spec's uploads. The upload field titles a file after its name,
 * dashes turned into spaces.
 */
export function uniqueName(label: string): { file: string; title: string } {
    const file = `fib-${label}-${Date.now().toString(36)}.png`;
    return { file, title: file.replace(/\.png$/, '').replace(/-/g, ' ') };
}

/** Open a seeded page's CMS edit form through the link its front end renders (no IDs needed). */
export async function openEditForm(page: Page, segment: string): Promise<number> {
    await page.goto(`/${segment}?stage=Stage`);
    const href = (await page.locator('#cms-edit').getAttribute('href'))!;
    await page.goto(href);
    await expect(field(page).locator('div.uploadfield')).toBeVisible();
    return Number(href.match(/\/show\/(\d+)/)![1]);
}

/** The featured images field holder in the page edit form. */
export function field(page: Page): Locator {
    return page.locator('#Form_EditForm_FeaturedImages_Holder');
}

/** The field's schema (the React UploadField's settings) from its data-schema attribute. */
export async function fieldSchema(page: Page): Promise<any> {
    return JSON.parse((await page.locator('input#Form_EditForm_FeaturedImages').getAttribute('data-schema'))!);
}

/** The titles of the files in the field, in the field's order. */
export async function fieldTitles(page: Page): Promise<string[]> {
    return (await field(page).locator('.uploadfield-item__title').allTextContents()).map((t) => t.trim());
}

/** Remove every file from the field (detaches them; the files stay in the asset store). */
export async function removeAll(page: Page): Promise<void> {
    const remove = field(page).locator('.uploadfield-item__remove-btn');
    while ((await remove.count()) > 0) {
        await remove.first().click();
    }
    await expect(field(page).locator('.uploadfield-item')).toHaveCount(0);
}

/**
 * Upload one file through the field's own dropzone input and wait until the field lists it as
 * uploaded. One at a time, so the field's order is the upload order (the field uploads in
 * parallel otherwise, and finishes in any order).
 */
export async function upload(page: Page, file: string): Promise<void> {
    const name = file.replace(/\.png$/, '').replace(/-/g, ' ');
    const done = page.waitForResponse((r) => /\/field\/FeaturedImages\/upload$/.test(r.url()) && r.request().method() === 'POST');
    await page.locator('input.dz-input-FeaturedImages').setInputFiles([{ name: file, mimeType: 'image/png', buffer: PNG }]);
    expect((await done).status(), `upload of ${file}`).toBe(200);
    // Uploaded = the item carries the new file's ID in its hidden FeaturedImages[Files][] input.
    const item = field(page).locator('.uploadfield-item', { has: page.locator('.uploadfield-item__title', { hasText: name }) });
    await expect(item.locator('input[type="hidden"][name="FeaturedImages[Files][]"]')).toHaveValue(/^\d+$/);
}

/** Click a page form action (Save / Publish) and return its POST; asserts it was AJAX with 200. */
export async function submit(page: Page, id: number, action: 'save' | 'publish'): Promise<Request> {
    // Let the CMS change tracker settle first. It re-scans the form on a 250 ms throttle (leading
    // AND trailing) after every click/change in it, and on Silverstripe 6 a trailing scan that runs
    // after the save has replaced the form throws "Cannot read properties of null (reading
    // 'prepValueForChangeTracker')" (admin's changetracker reads the new Content textarea's editor
    // before entwine has attached it). That is core admin behaviour, not this module's, and a person
    // rarely clicks Save within 250 ms of the last edit. Seen in 5 of 6 runs without this wait.
    await page.waitForTimeout(300);
    const url = new RegExp(`/admin/pages/edit/EditForm/${id}(\\?|$)`);
    const posted = page.waitForRequest((r) => r.method() === 'POST' && url.test(r.url()));
    await page.locator(`#Form_EditForm_action_${action}`).click();
    const request = await posted;
    expect(['xhr', 'fetch'], `the ${action} is an AJAX request`).toContain(request.resourceType());
    expect((await request.response())?.status(), `${action} response status`).toBe(200);
    return request;
}

/** The file IDs a page form POST sends for the field, in order. */
export function postedFileIds(request: Request): string[] {
    return new URLSearchParams(request.postData() ?? '').getAll('FeaturedImages[Files][]');
}

/** What the page's front end renders: $PageImage and $PageImages, as image titles and URLs. */
export async function frontEnd(page: Page, url: string): Promise<{ first: string | null; all: string[]; srcs: string[] }> {
    const response = await page.goto(url);
    expect(response?.status(), `GET ${url}`).toBe(200);
    // Read without auto-waiting: an empty list is a valid answer here, not something to wait out.
    const firsts = await page.locator('#page-image img').evaluateAll((imgs) => imgs.map((i) => i.getAttribute('alt') ?? ''));
    const all = await page.locator('#page-images img').evaluateAll((imgs) => imgs.map((i) => i.getAttribute('alt') ?? ''));
    const srcs = await page.locator('#page-images img').evaluateAll((imgs) => imgs.map((i) => (i as HTMLImageElement).src));
    return { first: firsts[0] ?? null, all, srcs };
}

/**
 * Drag the file at index `from` onto the position of the file at index `to`, by its drag handle
 * (the sortable upload field from bummzack/sortablefile).
 */
export async function dragFile(page: Page, from: number, to: number): Promise<void> {
    const handles = field(page).locator('.sortable-item__handle');
    // The pointer only reaches what is inside the viewport: bring the list into view first.
    await field(page).evaluate((e) => e.scrollIntoView({ block: 'center' }));
    const a = (await handles.nth(from).boundingBox())!;
    const b = (await handles.nth(to).boundingBox())!;
    await page.mouse.move(a.x + a.width / 2, a.y + a.height / 2);
    await page.mouse.down();
    // A few steps: the sortable library only starts dragging after the pointer has moved.
    await page.mouse.move(a.x + a.width / 2, a.y + a.height / 2 + (b.y > a.y ? 10 : -10), { steps: 5 });
    await page.mouse.move(b.x + b.width / 2, b.y + (b.y > a.y ? b.height - 2 : 2), { steps: 10 });
    await page.mouse.up();
}
