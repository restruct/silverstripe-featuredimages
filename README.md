Extension to quickly add a featured image to a pagetype
=======================================================

## Features

* Adds a simple uploadfield to a page for a featuredimage

![One/single image](docs/assets/single_image.png)

or set of featured images (max amount configurable)

![multiple sortable images](docs/assets/multiple_images.png)

## Requirements

* SilverStripe 6 or newer

## Installation

```
composer require restruct/silverstripe-featuredimages 
```

## Apply extension to desired pagetypes:

Add to config.yml (max_featured_images is optional, default = 1):

```yaml
Page:
  extensions:
    - '\Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension'
  max_featured_images: 3
```

And use in templates as 
```
$PageImage
```
or
```
<% loop $PageImages %>
    $Me
<% end_loop %>
