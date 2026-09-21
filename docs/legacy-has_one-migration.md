# Legacy has_one -> many_many migration (historical reference)

Between 1.x and 2.x this module changed `FeaturedImage` from a `has_one` to a `FeaturedImages`
`many_many`. 2.x auto-migrated existing `has_one` values on the fly. In 3.x that auto-migration was
moved into a `FeaturedImageUpdateTask` build task, which was never finished: it was shipped disabled
and its body was unreachable behind an unconditional `die()` explaining that it still needed to be
rewritten to loop over every DataObject the extension is applied to.

The task class was removed in the release that widened this module to Silverstripe 4, 5 and 6,
because its build-task signature differs across those majors and the class did nothing on any of
them. The original migration code is kept here so the logic is not lost if someone picks the
migration up again.

Anyone still holding 1.x data has an un-migrated `FeaturedImageID` column on the base table. The
code below is the shape of the fix, not a working task: it only ever handled the class it was
invoked on, which is the limitation that stopped it being finished.

```php
// $class     = the DataObject class being migrated
// $baseclass = its base class; migration only ran when they were the same

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
```

Notes for anyone reviving this:

- Table and column names are interpolated straight into SQL. Any rewrite should use parameterised
  queries or the ORM instead.
- The `ALTER TABLE ... DROP "FeaturedImageID"` step is destructive and irreversible. Take a backup
  first, and consider leaving the legacy column in place as the original comment suggests.
- To do the job properly it needs to enumerate every class the extension is applied to, rather than
  relying on a single owner.
