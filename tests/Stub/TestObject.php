<?php

namespace Restruct\FeaturedImages\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test-only DataObject WITHOUT the Hierarchy extension, so the "no hierarchy to traverse"
 * branches of FirstFeaturedImagesUpTheHierarchy() and DescendantsFeaturedImages() can be
 * exercised. A plain DataObject is the realistic case: the extension may be applied to
 * anything, not only to pages.
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'FITestObject';

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText',
    ];
}
