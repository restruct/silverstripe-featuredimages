<?php

namespace Restruct\FiBrowser;

use Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension;
use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - a page type with the featured images extension, up to three images.
 *
 * Never loaded by a real install: it lives under tests/browser/, which carries a _manifest_exclude
 * marker, and the browser-test runner copies it into a scratch host's app/ before dev/build.
 * Written to load on both Silverstripe 5 and 6 (no class imports that moved between the two).
 *
 * A SiteTree subclass, not Page: the scratch host is a bare recipe-cms project without app/src/Page.
 * The extension is applied here, as the README does on Page, through the class's own config.
 * Every dev/build re-seeds one page per spec and detaches its images, so a run starts clean.
 */
class FiBPage extends SiteTree
{
    # Short table name: the many_many join table is "{table}_FeaturedImages", and MySQL caps table
    # names at 64 characters.
    private static $table_name = 'FiBPage';

    private static $extensions = [
        FeaturedImageExtension::class,
    ];

    # The README's optional setting (default 1).
    private static $max_featured_images = 3;

    /** URL segment => title of the pages this class seeds (one per spec). */
    protected const SEEDS = [
        'fib-multi' => 'Featured multi',
        'fib-publish' => 'Featured publish',
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # requireDefaultRecords() runs once per class in the hierarchy; each class seeds its own.
        foreach (static::SEEDS as $segment => $title) {
            $page = static::get()->filter(['ClassName' => static::class, 'URLSegment' => $segment])->first()
                ?: static::create(['URLSegment' => $segment]);
            $page->Title = $title;
            $page->write();
            $page->FeaturedImages()->removeAll();
            $page->publishRecursive();
        }
    }
}
