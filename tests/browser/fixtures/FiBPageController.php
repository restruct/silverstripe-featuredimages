<?php

namespace Restruct\FiBrowser;

use SilverStripe\CMS\Controllers\ContentController;

/**
 * BROWSER-TEST FIXTURE ONLY - renders a fixture page's featured images without a theme.
 *
 * The scratch host has no theme and a fixture cannot ship a template (only a module's templates/
 * dir is scanned), so the page answers with minimal HTML built from the extension's template
 * methods: $PageImage (the first by sort order) and $PageImages (all, sorted).
 */
class FiBPageController extends ContentController
{
    public function index()
    {
        $page = $this->data();
        $first = $page->PageImage();
        $html = '<!DOCTYPE html><html><head><title>' . htmlspecialchars((string) $page->Title) . '</title></head><body>';
        # The specs open the page's CMS edit form from here, so they need no page IDs.
        $html .= '<a id="cms-edit" href="' . htmlspecialchars((string) $page->CMSEditLink()) . '">Edit</a>';
        $html .= '<div id="page-image">' . ($first ? $this->img($first) : '') . '</div>';
        $html .= '<ol id="page-images">';
        foreach ($page->PageImages() as $image) {
            $html .= '<li>' . $this->img($image) . '</li>';
        }
        $html .= '</ol></body></html>';
        return $html;
    }

    private function img($image): string
    {
        return sprintf(
            '<img src="%s" alt="%s" data-name="%s">',
            htmlspecialchars((string) $image->getURL()),
            htmlspecialchars((string) $image->Title),
            htmlspecialchars((string) $image->Name)
        );
    }
}
