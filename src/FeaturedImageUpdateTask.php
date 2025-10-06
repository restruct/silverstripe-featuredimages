<?php

namespace Restruct\SilverStripe\FeaturedImages;


use Override;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;


class FeaturedImageUpdateTask extends BuildTask
{
    public $owner;

    public $ownerBaseClass;

    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = false;

    /**
     * @var string $title Shown in the overview on the {@link TaskRunner}
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected string $title = 'Migrate legacy has_one featured image to many_many relation';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected static string $description = 'Between v1 & v2, featured image switched to a many_many, v2 auto-migrated existing has_ones. In v3 this functionality has been moved to a build task.';

    #[Override]
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // @TODO: the below function needs to be rewritten to loop over all DataObjects which have been extended with FeaturedImage for this task to actually work.
        die("FeaturedImageUpdateTask hasn't actually been fully implemented; run() needs to be rewritten to loop over all DataObjects which have been extended with FeaturedImage for this task to actually work");
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
