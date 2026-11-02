<?php

namespace Restruct\FiBrowser;

use Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - the content behind the README screenshots
 * (tests/browser/screenshots/, see its README.md): a published page at /featured-showcase with
 * three featured images, drawn with GD so no photo or client content is involved.
 * FiBShowcaseSinglePage is the same with the module's default of one image.
 *
 * Never loaded by a real install: it lives under tests/browser/, which carries a _manifest_exclude
 * marker, and the browser-test runner copies it into a scratch host's app/ before dev/build. The
 * browser specs do not use these pages. Written to load on both Silverstripe 5 and 6, and extends
 * SiteTree, not Page: the scratch host has no app Page class (see FiBPage).
 */
class FiBShowcasePage extends SiteTree
{
    # Short table name: the many_many join table is "{table}_FeaturedImages" (see FiBPage).
    private static $table_name = 'FiBShowcasePage';

    private static $extensions = [
        FeaturedImageExtension::class,
    ];

    # The README's example value.
    private static $max_featured_images = 3;

    /** URL segment of the seeded page. */
    protected const SEGMENT = 'featured-showcase';

    protected const TITLE = 'Summer walks';

    protected const CONTENT = '<p>Three short walks along the coast and through the hills, each about two hours.</p>';

    /** Images of the seeded page, in sort order: file name => scene drawn by draw(). */
    protected const IMAGES = [
        'coastal-path.jpg' => 'sea',
        'hill-ridge.jpg' => 'hills',
        'evening-lake.jpg' => 'lake',
    ];

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Without GD there is nothing to show; the specs do not need these pages.
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        # requireDefaultRecords() runs once per class in the hierarchy; each class seeds its own.
        $page = static::get()->filter(['ClassName' => static::class, 'URLSegment' => static::SEGMENT])->first()
            ?: static::create(['URLSegment' => static::SEGMENT]);
        $page->Title = static::TITLE;
        $page->Content = static::CONTENT;
        $page->write();

        # Re-attach on every build, in the declared order, so the screenshots are reproducible.
        $page->FeaturedImages()->removeAll();
        $sort = 1;
        foreach (static::IMAGES as $name => $scene) {
            $page->FeaturedImages()->add($this->seedImage($name, $scene), ['SortOrder' => $sort++]);
        }
        $page->publishRecursive();
    }

    /** A published Image in the module's default upload folder, drawn once and then reused. */
    private function seedImage(string $name, string $scene): Image
    {
        $filename = 'pageimages/' . $name;
        $image = Image::get()->filter('FileFilename', $filename)->first();
        if (!$image) {
            $image = Image::create();
            $image->setFromString($this->draw($scene), $filename);
            $image->write();
            $image->publishSingle();
        }
        # The CMS never generates thumbnails on display (asset-admin config "Generates: false"),
        # so an image seeded from code would show an empty tile in the upload field.
        AssetAdmin::singleton()->generateThumbnails($image);
        return $image;
    }

    /** Draw a simple 1200 x 800 landscape: a sky gradient, a sun and two layers of land. */
    private function draw(string $scene): string
    {
        $palettes = [
            # sky top, sky bottom, sun, far land, near land
            'sea' => [[0x7d, 0xc3, 0xe8], [0xe6, 0xf4, 0xfa], [0xff, 0xf3, 0xc4], [0x2f, 0x7f, 0xa8], [0x1d, 0x5b, 0x7a]],
            'hills' => [[0x9b, 0xc9, 0xe0], [0xf2, 0xf0, 0xd8], [0xff, 0xe9, 0xa8], [0x7a, 0xa8, 0x5a], [0x4c, 0x7d, 0x3a]],
            'lake' => [[0x3b, 0x3f, 0x8f], [0xf6, 0xa5, 0x6b], [0xff, 0xd2, 0x8a], [0x4a, 0x35, 0x6e], [0x2a, 0x22, 0x4a]],
        ];
        [$top, $bottom, $sun, $far, $near] = $palettes[$scene];
        $w = 1200;
        $h = 800;
        $gd = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $f = $y / ($h - 1);
            $rgb = array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $f), $top, $bottom);
            imageline($gd, 0, $y, $w - 1, $y, imagecolorallocate($gd, ...$rgb));
        }
        $sunX = $scene === 'lake' ? 820 : 300;
        imagefilledellipse($gd, $sunX, 330, 170, 170, imagecolorallocate($gd, ...$sun));

        # Far and near land as polylines across the width; the sea scene is flat water.
        $profiles = [
            'sea' => [[0, 560, 1200, 560], [0, 640, 260, 600, 520, 650, 1200, 690]],
            'hills' => [[0, 470, 220, 380, 470, 450, 760, 340, 1000, 430, 1200, 380], [0, 600, 300, 520, 640, 610, 900, 540, 1200, 600]],
            'lake' => [[0, 500, 180, 400, 360, 470, 600, 360, 840, 460, 1200, 420], [0, 620, 1200, 620]],
        ];
        foreach ([$far, $near] as $i => $colour) {
            $points = array_merge($profiles[$scene][$i], [$w, $h, 0, $h]);
            imagefilledpolygon($gd, $points, imagecolorallocate($gd, ...$colour));
        }

        ob_start();
        imagejpeg($gd, null, 82);
        return (string) ob_get_clean();
    }
}
