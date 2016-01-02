<?php

class FeaturedImageExtension extends SiteTreeExtension
{
    
    public static $db = array(
    );

    public static $has_one = array(
        "FeaturedImage" => "Image",
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->insertBefore(
            $uploadField = UploadField::create("FeaturedImage", _t("FeaturedImage.FeaturedImage", "Featured Image")),
            "Content"
        );
        $uploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
    }
}
