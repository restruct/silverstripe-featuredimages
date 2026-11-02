<?php

namespace Restruct\FiBrowser;

/**
 * BROWSER-TEST FIXTURE ONLY - FiBShowcasePage with the module's default of ONE image, for the
 * README's single-image screenshot.
 */
class FiBShowcaseSinglePage extends FiBShowcasePage
{
    private static $table_name = 'FiBShowcaseSinglePage';

    # Back to the extension's default (1). The extension is inherited from FiBShowcasePage.
    private static $max_featured_images = 1;

    protected const SEGMENT = 'featured-showcase-single';

    protected const TITLE = 'Our team';

    protected const CONTENT = '<p>A small team that likes to walk.</p>';

    protected const IMAGES = [
        'hill-ridge.jpg' => 'hills',
    ];
}
