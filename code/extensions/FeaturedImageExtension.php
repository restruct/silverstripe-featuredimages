<?php

class FeaturedImageExtension extends SiteTreeExtension {
	
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
	
	static $db = array(
	);

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
    public function PageImages(){
        return $this->owner->FeaturedImages()->Sort('SortOrder');
    }
	public function PageImage(){
        return $this->owner->FeaturedImages()->Sort('SortOrder')->First();
    }
	public function FeaturedImage(){
		return $this->owner->PageImage();
	}
	
	// Migrate 
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(!$this->owner->config()->upgrade_on_build) {
			return;
		}
		// Perform migrations (the legacy field will be left in the DB by the ORM)
		$class = $this->owner->class;
		$baseclass = $this->ownerBaseClass;
		if($baseclass==$class){
		// if(in_array('FeaturedImageExtension', Config::inst()->get($class, 'extensions'))){
			$rows = DB::query('SELECT * FROM "'.$baseclass.'"');
			$altered = false;
			foreach($rows as $page){
				if(array_key_exists('FeaturedImageID', $page) && $imageID = $page['FeaturedImageID']){
					DB::query('INSERT INTO "'.$baseclass.'_FeaturedImages" ('.$class.'ID, ImageID) VALUES ('.$page['ID'].', '.$page['FeaturedImageID'].')');
					$altered = true;
					//$page->FeaturedImages()->add($imageID);
					//$page->FeaturedImageID = null; // leave as is...
//					$page->write();
				}
			}
			// Now drop the legacy field
			if($altered){
				DB::query('ALTER TABLE "'.$baseclass.'" DROP "FeaturedImageID"');
				DB::alteration_message('Migrated FeaturedImages to many_many on '.$baseclass, 'changed');
			}
		}
	}

	function updateCMSFields(FieldList $fields) {
		
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
	public function ContentHasFeaturedImageShortcode(){
		if($this->owner->Content){
			if(stripos($this->owner->Content,'[featuredimage]') !== false){
				return true;
			}
			return false;
		}
		return null; //eg unknown
	}
	
}

/**
 * Upgrade from has_one to has_many
 */
class UserFormsUpgradeTask extends BuildTask {
	protected $title = "Upgrade FeaturedImages from has_one to many_many";
	protected $description = "Upgrade FeaturedImages from has_one to many_many";
	public function run($request) {
		$this->log("Upgrading FeaturedImages module to many_many");
		
//		if($imageID = $this->owner->FeaturedImageID){
//			$this->owner->FeaturedImages()->add($imageID);
//			$this->owner->FeaturedImageID = null;
//			$this->owner->write();
//		}
		
		$this->log("Done");
	}
}

//class HLCLFixTitlesAndFeatImages extends BuildTask {
// 
//    protected $title = 'Update';
// 
//    protected $description = 'Removes "[/stuff/etc]" from title & moves first content image to featuredimage';
// 
//    protected $enabled = true;
//	
////	protected static $datearray = array(
////		array("Uitbreiding X-Caliber project", "28-11-2014"),
////	);
// 
//    function run($request) {
//		if(!$request->getVar('SourceID')){
//			print "Choose a SourceID e.g. ?SourceID=1";
//			return;
//		}
//		
//		$contentSourceID = trim($request->getVar('SourceID'));
//		$contentSource = (StaticSiteContentSource::get()->byID($contentSourceID));
//		$pages = $contentSource->Pages();
//		
//        //foreach(self::$datearray as $date){
//        foreach($pages as $page){
//			// skip if empty content
//			if($page->Content==""){ continue; }
//			// load Content
//			$doc = new DOMDocument();
//			try {
//				// suppress warnings while importing (the flags prevent dtd & html+body node insertion)
//				$doc->loadHTML(str_replace('o-bject', 'object', $page->Content), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
//			} catch (Exception $e) { continue; }
//			// find first image and save to FeaturedImage field
////				$xml = simplexml_import_dom($doc);
////				$res = $xml->xpath('//p/img/@src');
////				$src = $res[0]["src"][0];
//			$images = $doc->getElementsByTagName('img');
//			foreach($images as $img){
//				//Debug::dump($img);
//				//$imgattr = $img->attributes;
//				//Debug::dump($img->getAttribute('src'));
//				$src=$img->getAttribute('src');
//				if(!empty($src)){
////					print 'FOUND: '.$date[0].' src: '.$res[0]["src"][0].' </br>';
//					$fsimg = Image::find($src);
//					if($fsimg && $fsimg->exists()) $page->FeaturedImageID = $fsimg->ID;
//					// now remove the first image from HTML
//					$img->parentNode->removeChild($img);
//					$page->Content = $doc->saveHTML();
//				}
//				break; // just the first
//			}
//			
//			// remove these weird [/some/url/whatever] things from title
//			$title_parts = explode('[/', $page->Title);
//			$page->Title = array_shift($title_parts);
//			if(property_exists($page, 'LastName')){ $page->LastName = $page->Title; } // for specialists
//				
//			// write to db
//			$page->write();
//			$page->doPublish();
//			print "Fixed: ".$page->Title." (ID: ".$page->ID.")";
//		}
//    }
//}
