<?php

namespace Restruct\FeaturedImages\Tests\Stub;

use SilverStripe\CMS\Model\SiteTree;

/**
 * Test-only page type. The extension is applied to this rather than to Page so the
 * test suite does not depend on how the host project has configured its own Page class.
 */
class TestPage extends SiteTree implements \SilverStripe\Dev\TestOnly
{
    private static $table_name = 'FITestPage';
}
