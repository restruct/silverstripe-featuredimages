import {
    test,
    expect,
    dragFile,
    field,
    fieldSchema,
    fieldTitles,
    frontEnd,
    openEditForm,
    postedFileIds,
    removeAll,
    submit,
    uniqueName,
    upload,
} from './support';

// FeaturedImageExtension::updateCMSFields() adds a "Page Image(s)" upload field to the page edit
// form (sortable, from bummzack/sortablefile), limited to max_featured_images, uploading to the
// upload_folder; $PageImage / $PageImages read the images back in the saved order, and $owns
// publishes them with the page.

test('the page edit form gets a sortable "Page Image(s)" field right before Content', async ({ page }) => {
    await openEditForm(page, 'fib-multi');

    await expect(field(page).locator('label').first()).toHaveText('Page Image(s)');
    // insertBefore("Content"): the field holder is the Content holder's previous sibling.
    const next = await field(page).evaluate((e) => e.nextElementSibling?.id);
    expect(next, 'the holder right after the field').toBe('Form_EditForm_Content_Holder');

    const schema = await fieldSchema(page);
    expect(schema.title).toBe('Page Image(s)');
    // SortableUploadField: sortable, with a drag handle per file.
    expect(schema.sortable, 'sortable upload field').toBe(true);
    // max_featured_images on the fixture page type.
    expect(schema.data.maxFiles).toBe(3);
});

test('the default of one image: the field takes one file, then stops offering uploads', async ({ page }) => {
    const id = await openEditForm(page, 'fib-single');
    expect((await fieldSchema(page)).data.maxFiles).toBe(1);

    await removeAll(page);
    await expect(field(page).locator('.uploadfield__dropzone')).not.toHaveClass(/uploadfield__dropzone--hidden/);
    const { file, title } = uniqueName('single');
    await upload(page, file);
    // At the limit the upload field hides its dropzone ("Upload new" / "Choose existing").
    await expect(field(page).locator('.uploadfield__dropzone')).toHaveClass(/uploadfield__dropzone--hidden/);

    await submit(page, id, 'save');
    const shown = await frontEnd(page, '/fib-single?stage=Stage');
    expect(shown.first).toBe(title);
    expect(shown.all).toEqual([title]);
});

test('images are saved in the field order, re-sorted by drag, and rendered by $PageImage / $PageImages', async ({ page }) => {
    const id = await openEditForm(page, 'fib-multi');
    await removeAll(page);

    const a = uniqueName('a');
    const b = uniqueName('b');
    await upload(page, a.file);
    await upload(page, b.file);
    expect(await fieldTitles(page)).toEqual([a.title, b.title]);

    const saved = await submit(page, id, 'save');
    expect(postedFileIds(saved)).toHaveLength(2);

    let shown = await frontEnd(page, '/fib-multi?stage=Stage');
    expect(shown.all, '$PageImages').toEqual([a.title, b.title]);
    expect(shown.first, '$PageImage is the first by sort order').toBe(a.title);
    // Uploaded into the configured upload_folder (default "pageimages").
    for (const src of shown.srcs) {
        expect(src, 'image URL').toMatch(/\/assets\/pageimages\//);
    }

    // Drag the second file above the first, save: the new order is what the templates get.
    await openEditForm(page, 'fib-multi');
    expect(await fieldTitles(page)).toEqual([a.title, b.title]);
    await dragFile(page, 1, 0);
    await expect.poll(() => fieldTitles(page)).toEqual([b.title, a.title]);
    const resorted = await submit(page, id, 'save');
    expect(postedFileIds(resorted)).toEqual([...postedFileIds(saved)].reverse());

    shown = await frontEnd(page, '/fib-multi?stage=Stage');
    expect(shown.all, '$PageImages after re-sorting').toEqual([b.title, a.title]);
    expect(shown.first, '$PageImage after re-sorting').toBe(b.title);
});

test('publishing the page publishes its images ($owns)', async ({ page, browser }) => {
    const id = await openEditForm(page, 'fib-publish');
    await removeAll(page);
    const { file, title } = uniqueName('pub');
    await upload(page, file);
    // A fresh upload is a draft file.
    await expect(field(page).locator('.uploadfield-item', { hasText: title }).locator('.uploadfield-item__status')).toHaveText(/Draft/);

    await submit(page, id, 'publish');

    // A visitor (no session) sees the published page with the image at its public URL.
    const visitor = await browser.newContext();
    try {
        const anon = await visitor.newPage();
        const shown = await frontEnd(anon, '/fib-publish');
        expect(shown.first).toBe(title);
        const src = shown.srcs[0];
        // Public asset URL: no hash directory, which only protected (draft) files get.
        expect(new URL(src).pathname).toBe(`/assets/pageimages/${file}`);
        const image = await anon.request.get(src);
        expect(image.status(), `GET ${src} as a visitor`).toBe(200);
        expect(image.headers()['content-type']).toBe('image/png');
    } finally {
        await visitor.close();
    }
});
