<?php

namespace Restruct\SilverStripe\FeaturedImages;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\CMS\Model\SiteTreeExtension;
use Bummzack\SortableFile\Forms\SortableUploadField;

class FeaturedImageExtension extends SiteTreeExtension
{

    /**
     * @config null=unlimited, default=1
     */
    private static $max_featured_images = 1;

    /**
     * @config null=unlimited, default=1
     */
    private static $upload_folder = 'pageimages';

    private static $many_many = array(
        'FeaturedImages' => Image::class,
    );
    
    // this adds the SortOrder field to the relation table.
    // Please note that the key (in this case 'Images')
    // has to be the same key as in the $many_many definition!
    private static $many_many_extraFields = array(
        'FeaturedImages' => array('SortOrder' => 'Int')
    );

    // New SS4 publishing mechanism
    private static $owns = [
        'FeaturedImages',
    ];
    
    // Use this in your templates to get the correctly sorted images
    // OR use $FeaturedImages.Sort('SortOrder') in your templates which
    // will unclutter your PHP classes
    public function PageImages()
    {
        return $this->owner->FeaturedImages()->Sort('SortOrder');
    }

    public function PageImage()
    {
        return $this->owner->FeaturedImages()->Sort('SortOrder')->First();
    }

    public function FeaturedImage()
    {
        return $this->owner->PageImage();
    }

    function updateCMSFields(FieldList $fields)
    {
        $fields->insertBefore(
            // Should this be updated to the new FileHandleField class?
            // Injector::inst()->create(FileHandleField::class, 'Files')
            $uploadField = SortableUploadField::create("FeaturedImages",
                _t("FeaturedImage.FeaturedImages", "Page Image(s)")),
            "Content"
        );
        $uploadField->setFolderName(Config::inst()->get($this->owner->ClassName, 'upload_folder'));
        $uploadField->setAllowedFileCategories('image/supported');
        $uploadField->setAllowedMaxFileNumber(Config::inst()->get(get_class($this->owner), 'max_featured_images'));

    }

    // Check if [featuredimage] has been place in this page's Content
    public function ContentHasFeaturedImageShortcode()
    {
        if ($this->owner->Content) {
            if (stripos($this->owner->Content, '[featuredimage]') !== false) {
                return true;
            }
            return false;
        }
        return null; //eg unknown
    }

}
