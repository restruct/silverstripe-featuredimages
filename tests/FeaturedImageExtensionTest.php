<?php

namespace Restruct\FeaturedImages\Tests;

use Bummzack\SortableFile\Forms\SortableUploadField;
use Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension;
use Restruct\FeaturedImages\Tests\Stub\TestObject;
use Restruct\FeaturedImages\Tests\Stub\TestPage;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataList;

/**
 * Behavioural tests for the FeaturedImages extension.
 *
 * These are deliberately written against the module's PUBLIC contract - the relation, the
 * template accessors, the CMS field and the two hierarchy helpers - rather than against its
 * internals, so they keep their meaning across Silverstripe majors.
 *
 * Compatibility note: this suite runs under PHPUnit 9 (Silverstripe 4 and 5) and PHPUnit 11
 * (Silverstripe 6). Keep it free of doc-comment metadata (@test, @dataProvider), make any data
 * provider static, and avoid assertions removed after PHPUnit 9.
 */
class FeaturedImageExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'FeaturedImageExtensionTest.yml';

    protected static $extra_dataobjects = [
        TestPage::class,
        TestObject::class,
    ];

    protected static $required_extensions = [
        TestPage::class => [FeaturedImageExtension::class],
        TestObject::class => [FeaturedImageExtension::class],
    ];

    /**
     * Attach images to a page in a known order. Returns the page.
     */
    private function attachImages(string $pageIdentifier, array $imageIdentifiersInOrder): TestPage
    {
        $page = $this->objFromFixture(TestPage::class, $pageIdentifier);
        $sortOrder = 0;
        foreach ($imageIdentifiersInOrder as $imageIdentifier) {
            $image = $this->objFromFixture(Image::class, $imageIdentifier);
            $page->FeaturedImages()->add($image, ['SortOrder' => ++$sortOrder]);
        }

        return $page;
    }

    /**
     * A non-hierarchical object with exactly one image attached.
     */
    private function objectWithOneImage(): TestObject
    {
        $object = $this->objFromFixture(TestObject::class, 'objectWithImages');
        $object->FeaturedImages()->add($this->objFromFixture(Image::class, 'imageA'), ['SortOrder' => 1]);

        return $object;
    }

    // ---------------------------------------------------------------- wiring

    public function testExtensionIsApplied()
    {
        $page = TestPage::create();
        $this->assertTrue($page->hasExtension(FeaturedImageExtension::class));
    }

    public function testManyManyRelationExistsAndAcceptsImages()
    {
        $page = $this->attachImages('pageWithImages', ['imageA']);
        $this->assertSame(1, $page->FeaturedImages()->count());
        $this->assertInstanceOf(Image::class, $page->FeaturedImages()->first());
    }

    public function testRelationIsSortableViaTheSortOrderExtraField()
    {
        // attached B first, then A - so SortOrder disagrees with insertion order by ID
        $page = $this->attachImages('pageWithImages', ['imageB', 'imageA']);

        $titles = $page->PageImages()->column('Title');
        $this->assertSame(['Image B', 'Image A'], $titles, 'PageImages() must sort by SortOrder, not by ID');
    }

    // ------------------------------------------------------- template accessors

    public function testPageImageReturnsTheFirstImageBySortOrder()
    {
        $page = $this->attachImages('pageWithImages', ['imageB', 'imageA']);
        $this->assertSame('Image B', $page->PageImage()->Title);
    }

    public function testPageImageReturnsNullWhenNoImagesAttached()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $this->assertNull($page->PageImage());
    }

    public function testFeaturedImageIsAnAliasOfPageImage()
    {
        $page = $this->attachImages('pageWithImages', ['imageA']);
        $this->assertSame($page->PageImage()->ID, $page->FeaturedImage()->ID);
    }

    // ------------------------------------------------------------- CMS field

    public function testUploadFieldIsInsertedBeforeTheContentField()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $fields = $page->getCMSFields();

        $names = [];
        foreach ($fields->dataFields() as $field) {
            $names[] = $field->getName();
        }

        $imagesAt = array_search('FeaturedImages', $names, true);
        $contentAt = array_search('Content', $names, true);

        $this->assertNotFalse($imagesAt, 'FeaturedImages field must be present in the CMS fields');
        $this->assertNotFalse($contentAt, 'This test is meaningless without a Content field');
        $this->assertLessThan($contentAt, $imagesAt, 'FeaturedImages must be inserted BEFORE Content');
    }

    public function testUploadFieldIsAppendedWhenThereIsNoContentField()
    {
        // a plain DataObject has no Content field in its CMS fields by default
        $object = $this->objFromFixture(TestObject::class, 'objectWithoutImages');
        $fields = $object->getCMSFields();

        $this->assertNotNull(
            $fields->dataFieldByName('FeaturedImages'),
            'The field must still be added when there is nothing to insert before'
        );
    }

    public function testUploadFieldUsesSortableUploadFieldWhenItIsAvailable()
    {
        if (!class_exists(SortableUploadField::class)) {
            $this->markTestSkipped('bummzack/sortablefile is not installed');
        }

        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $field = $page->getCMSFields()->dataFieldByName('FeaturedImages');

        $this->assertInstanceOf(SortableUploadField::class, $field);
    }

    public function testMaxFeaturedImagesConfigIsAppliedToTheField()
    {
        Config::modify()->set(TestPage::class, 'max_featured_images', 7);

        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $field = $page->getCMSFields()->dataFieldByName('FeaturedImages');

        $this->assertSame(7, $field->getAllowedMaxFileNumber());
    }

    public function testUploadFolderConfigIsAppliedToTheField()
    {
        Config::modify()->set(TestPage::class, 'upload_folder', 'custom-folder');

        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $field = $page->getCMSFields()->dataFieldByName('FeaturedImages');

        $this->assertSame('custom-folder', $field->getFolderName());
    }

    public function testUploadFieldRenders()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $fields = $page->getCMSFields();
        // a FormField needs to belong to a Form before it can render: it calls Link()
        Form::create(null, 'TestForm', $fields, FieldList::create());

        $html = (string) $fields->dataFieldByName('FeaturedImages')->FieldHolder();
        $this->assertNotSame('', $html, 'The upload field must render to something');
    }

    // ------------------------------------------------------------- shortcode

    public function testContentHasFeaturedImageShortcodeDetectsTheShortcode()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithShortcode');
        $this->assertTrue($page->ContentHasFeaturedImageShortcode());
    }

    public function testContentHasFeaturedImageShortcodeIsCaseInsensitive()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithUppercaseShortcode');
        $this->assertTrue($page->ContentHasFeaturedImageShortcode());
    }

    public function testContentHasFeaturedImageShortcodeIsFalseWhenContentHasNoShortcode()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithoutImages');
        $this->assertFalse($page->ContentHasFeaturedImageShortcode());
    }

    public function testContentHasFeaturedImageShortcodeIsNullWhenThereIsNoContent()
    {
        $page = $this->objFromFixture(TestPage::class, 'pageWithoutContent');
        $this->assertNull(
            $page->ContentHasFeaturedImageShortcode(),
            'Empty content means "unknown", which is deliberately not the same as false'
        );
    }

    // --------------------------------------------------- up the hierarchy

    public function testFirstFeaturedImagesUpTheHierarchyIgnoresOwnImagesByDefault()
    {
        $this->attachImages('parentPage', ['imageA']);
        $child = $this->attachImages('childPage', ['imageB']);

        $result = $child->FirstFeaturedImagesUpTheHierarchy();

        $this->assertInstanceOf(DataList::class, $result);
        $this->assertSame(['Image A'], $result->column('Title'), 'Own images must be skipped unless asked for');
    }

    public function testFirstFeaturedImagesUpTheHierarchyReturnsOwnImagesWhenIncludeOwnIsSet()
    {
        $this->attachImages('parentPage', ['imageA']);
        $child = $this->attachImages('childPage', ['imageB']);

        $this->assertSame(['Image B'], $child->FirstFeaturedImagesUpTheHierarchy(true)->column('Title'));
    }

    public function testFirstFeaturedImagesUpTheHierarchyWalksPastAnEmptyParent()
    {
        $this->attachImages('grandparentPage', ['imageA']);
        // parentPage deliberately has no images
        $child = $this->objFromFixture(TestPage::class, 'childPage');

        $this->assertSame(
            ['Image A'],
            $child->FirstFeaturedImagesUpTheHierarchy()->column('Title'),
            'An empty parent must not stop the walk up the hierarchy'
        );
    }

    public function testFirstFeaturedImagesUpTheHierarchyReturnsEmptyWhenNothingIsFound()
    {
        $child = $this->objFromFixture(TestPage::class, 'childPage');
        $this->assertCount(0, $child->FirstFeaturedImagesUpTheHierarchy());
    }

    public function testFirstFeaturedImagesUpTheHierarchyHandlesObjectsWithoutHierarchy()
    {
        $object = $this->objectWithOneImage();

        $this->assertCount(0, $object->FirstFeaturedImagesUpTheHierarchy(), 'Without includeOwn: empty');
        $this->assertCount(1, $object->FirstFeaturedImagesUpTheHierarchy(true), 'With includeOwn: its own images');
    }

    // ----------------------------------------------------- down the hierarchy

    public function testDescendantsFeaturedImagesReturnsImagesOfDirectChildren()
    {
        $this->attachImages('childPage', ['imageA']);
        $this->attachImages('siblingPage', ['imageB']);
        $parent = $this->objFromFixture(TestPage::class, 'parentPage');

        $titles = $parent->DescendantsFeaturedImages()->column('Title');
        sort($titles);

        $this->assertSame(['Image A', 'Image B'], $titles);
    }

    public function testDescendantsFeaturedImagesKeepsEveryChildWhenIncludingOwn()
    {
        $this->attachImages('parentPage', ['imageC']);
        $this->attachImages('childPage', ['imageA']);
        $this->attachImages('siblingPage', ['imageB']);
        $parent = $this->objFromFixture(TestPage::class, 'parentPage');

        $titles = $parent->DescendantsFeaturedImages(true)->column('Title');
        sort($titles);

        // Regression: including own images must not drop a child from the result set.
        $this->assertSame(['Image A', 'Image B', 'Image C'], $titles);
    }

    public function testDescendantsFeaturedImagesIsShallowByDefaultAndDeepWhenRecursive()
    {
        $this->attachImages('childPage', ['imageA']);
        $this->attachImages('grandchildPage', ['imageB']);
        $parent = $this->objFromFixture(TestPage::class, 'parentPage');

        $this->assertSame(['Image A'], $parent->DescendantsFeaturedImages()->column('Title'));

        $recursive = $parent->DescendantsFeaturedImages(false, true)->column('Title');
        sort($recursive);
        $this->assertSame(['Image A', 'Image B'], $recursive);
    }

    public function testDescendantsFeaturedImagesHandlesObjectsWithoutHierarchy()
    {
        $object = $this->objectWithOneImage();

        $this->assertCount(0, $object->DescendantsFeaturedImages());
        $this->assertCount(1, $object->DescendantsFeaturedImages(true));
    }
}
