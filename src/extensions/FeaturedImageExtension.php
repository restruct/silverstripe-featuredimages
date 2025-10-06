<?php

namespace Restruct\SilverStripe\FeaturedImages;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;

class FeaturedImageExtension extends Extension
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
        // Should this be updated to the new FileHandleField class?
        // Injector::inst()->create(FileHandleField::class, 'Files')
        $uploadFieldClass = class_exists('Bummzack\SortableFile\Forms\SortableUploadField')
            ? 'Bummzack\SortableFile\Forms\SortableUploadField' : UploadField::class;
        $featImgField = $uploadFieldClass::create("FeaturedImages", _t("FeaturedImage.FeaturedImages", "Page Image(s)"));
        $featImgField->setFolderName(Config::inst()->get($this->owner->ClassName, 'upload_folder'));
        $featImgField->setAllowedFileCategories('image/supported');
        $featImgField->setAllowedMaxFileNumber(Config::inst()->get(get_class($this->owner), 'max_featured_images'));

        // if we have a Content field, insert before that -- else just append to Main tab
        if($fields->dataFieldByName("Content")) {
            $fields->insertBefore("Content", $featImgField);
        } else {
            $fields->addFieldToTab('Root.Main', $featImgField);
        }

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

    /**
     * Get featured image(s) from the first object in self or parents-hierachy which has any.
     *
     *      $FirstFeaturedImagesUpTheHierarchy.First
     *
     * @param boolean $includeOwn include own images in result
     * @param boolean $recursively get from childrens children as well (and their children, etc)
     * @return DataList
     */
    public function FirstFeaturedImagesUpTheHierarchy($includeOwn = false, $recursively = false): DataList
    {
        // if self included AND available on self
        if (boolval($includeOwn) && $this->owner->FeaturedImages()->count()) {
            return $this->owner->FeaturedImages();
        }
        // if no Parents hierarchy available to traverse, return own IF self included, empty result otherwise
        if ( ! $this->owner->hasExtension(Hierarchy::class)) {
            return boolval($includeOwn) ? $this->owner->FeaturedImages() : $this->owner->FeaturedImages()->filter('ID',-1); // return empty result
        }

        // else (own not included/none set on self AND parents to traverse), return first set found up the hierarchy
        $object = $this->owner;
        while($object = $object->getParent()) {
            if($object->FeaturedImages()->count()) {
                return $object->FeaturedImages();
            }
        }

        // return empty result if none found in parents as well
        return $this->owner->FeaturedImages()->filter('ID',-1);
    }

    /**
     * Get a list of featured images of (self and) children, optionally recursively.
     * (copied/adapted from i-lateral/silverstripe-featuredimage)
     *
     *      $DescendantsFeaturedImages.First
     *
     * @param boolean $includeOwn include own images in result
     * @param boolean $recursively get from childrens children as well (and their children, etc)
     * @return DataList
     */
    public function DescendantsFeaturedImages($includeOwn = false, $recursively = false): DataList
    {
        // if no Children hierarchy available to traverse, return own IF self included, empty result otherwise
        if ( ! $this->owner->hasExtension(Hierarchy::class)) {
            return boolval($includeOwn) ? $this->owner->FeaturedImages() : $this->owner->FeaturedImages()->filter('ID',-1); // return empty result
        }

        /** @var DataObject */
        $owner = $this->getOwner();
        $owner_class = $owner->ClassName;

        $HolderIDs = boolval($includeOwn) ? [ $owner->ID ] : [];
        $HolderIDs += $recursively ? $owner->getDescendantIDList() : $owner->AllChildren()->column('ID');

        return $owner_class::get()->byIDs($HolderIDs)->relation('FeaturedImages');
    }
}
