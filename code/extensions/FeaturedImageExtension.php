<?php

class FeaturedImageExtension extends SiteTreeExtension {
	
	static $db = array(
	);

	static $has_one = array(
		"FeaturedImage" => "Image",
	);

	function updateCMSFields(FieldList $fields) {
		
		$fields->insertBefore(
			$uploadField = UploadField::create("FeaturedImage", _t("FeaturedImage.FeaturedImage", "Featured Image")),
			"Content"
		);
		$uploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
		
	}
	
}