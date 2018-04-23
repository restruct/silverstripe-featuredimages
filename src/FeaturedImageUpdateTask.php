<?php

namespace Restruct\SilverStripe\FeaturedImages;


use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;

use SilverStripe\ORM\DB;



class FeaturedImageUpdateTask extends BuildTask
{
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = false;

    /**
     * @var string $title Shown in the overview on the {@link TaskRunner}
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Migrate legacy has_one featured image to many_many relation';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Between v1 & v2, featured image switched to a many_many, v2 auto-migrated existing has_ones. In v3 this functionality has been moved to a build task.';

    /**
     * Migrate legacy has_one featured image to many_many relation
     *
     * @param HTTPRequest $request
     * @return
     */
    public function run($request)
    {
        // @TODO: the below function needs to be rewritten to loop over all DataObjects which have been extended with FeaturedImage for this task to actually work.
        die("FeaturedImageUpdateTask hasn't actually been fully implemented; run() needs to be rewritten to loop over all DataObjects which have been extended with FeaturedImage for this task to actually work");

        // Perform migrations (the legacy field will be left in the DB by the ORM)
        $class = $this->owner->class;
        $baseclass = $this->ownerBaseClass;
        // check if table exists before attempting update, else this may throw an error on new installs
        if ($baseclass == $class && array_search($baseclass, DB::table_list())) {
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

//    static function getDecoratedBy($extension){
//        $classes = array();
//        foreach(ClassInfo::implementorsOf('Ex') as $className) {
//            if (ClassInfo::has_extension($className, $extension)){
//                $classes[] = $classname;
//            }
//        }
//        return $classes;
//    }
}