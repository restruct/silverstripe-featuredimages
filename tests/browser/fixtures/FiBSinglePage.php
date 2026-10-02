<?php

namespace Restruct\FiBrowser;

/**
 * BROWSER-TEST FIXTURE ONLY - the same page type with the module's default of ONE image.
 */
class FiBSinglePage extends FiBPage
{
    private static $table_name = 'FiBSinglePage';

    # Back to the extension's default (1). The extension is inherited from FiBPage.
    private static $max_featured_images = 1;

    protected const SEEDS = [
        'fib-single' => 'Featured single',
    ];
}
