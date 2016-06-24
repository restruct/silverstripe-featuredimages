<?php

class FeaturedImageExtension extends SiteTreeExtension
{

    /**
     * @config automatically upgrade on dev/build?
     */
    private static $upgrade_on_build = true;

    /**
     * @config null=unlimited, default=1
     */
    private static $max_featured_images = 1;

    /**
     * @config null=unlimited, default=1
     */
    private static $upload_folder = 'pageimages';

    static $db = array();

//	static $has_one = array(
//		"FeaturedImage" => "Image",
//	);

    static $many_many = array(
        'FeaturedImages' => "Image",
    );
    // this adds the SortOrder field to the relation table.
    // Please note that the key (in this case 'Images')
    // has to be the same key as in the $many_many definition!
    private static $many_many_extraFields = array(
        'FeaturedImages' => array('SortOrder' => 'Int')
    );
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

    // Migrate
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (!$this->owner->config()->upgrade_on_build) {
            return;
        }
        // Perform migrations (the legacy field will be left in the DB by the ORM)
        $class = $this->owner->class;
        $baseclass = $this->ownerBaseClass;
        if ($baseclass == $class) {
            // if(in_array('FeaturedImageExtension', Config::inst()->get($class, 'extensions'))){
            $rows = DB::query('SELECT * FROM "' . $baseclass . '"');
            $altered = false;
            foreach ($rows as $page) {
                if (array_key_exists('FeaturedImageID', $page) && $imageID = $page['FeaturedImageID']) {
                    DB::query('INSERT INTO "' . $baseclass . '_FeaturedImages" (' . $class . 'ID, ImageID) VALUES (' . $page['ID'] . ', ' . $page['FeaturedImageID'] . ')');
                    $altered = true;
                    //$page->FeaturedImages()->add($imageID);
                    //$page->FeaturedImageID = null; // leave as is...
//					$page->write();
                }
            }
            // Now drop the legacy field
            if ($altered) {
                DB::query('ALTER TABLE "' . $baseclass . '" DROP "FeaturedImageID"');
                DB::alteration_message('Migrated FeaturedImages to many_many on ' . $baseclass, 'changed');
            }
        }
    }

    function updateCMSFields(FieldList $fields)
    {

        $fields->insertBefore(
            $uploadField = new SortableUploadField("FeaturedImages",
                _t("FeaturedImage.FeaturedImages", "Page Image(s)")),
            "Content"
        );
        $uploadField->setFolderName(Config::inst()->get($this->owner->class, 'upload_folder'));
        //$uploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
        $uploadField->setAllowedFileCategories('image');
        $uploadField->setAllowedMaxFileNumber(Config::inst()->get($this->owner->class, 'max_featured_images'));

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
