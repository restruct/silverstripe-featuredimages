Extension to quickly add a featured image to a pagetype
=======================================================

## Features

* Adds a simple uploadfield to a page for a featuredimage

![One/single image](docs/assets/single_image.png)

or set of featured images (max amount configurable)

![multiple sortable images](docs/assets/multiple_images.png)

## Requirements

* Silverstripe 4, 5 or 6
* PHP 7.4 or newer

## Installation

```
composer require restruct/silverstripe-featuredimages
```

## Version compatibility

| Branch | Module version | Silverstripe | PHP |
|--------|----------------|--------------|-----|
| `main` | `4.1.x` and up | `^4 \|\| ^5 \|\| ^6` | `^7.4 \|\| ^8.0` |
| (tags only) | `4.0.x` | `^6` | `^8.3` |
| (tags only) | `3.x` | `^4 \|\| ^5` | `^7.1 \|\| ^8.0` |
| (tags only) | `2.x` | `^3` | `^5.6 \|\| ^7.0` |

`main` is the only maintained line: it supports every Silverstripe version this module still
targets, so there is no separate maintenance branch. A version branch will be created only when a
change cannot be made compatible across the supported range.

**`composer.json` is the source of truth** for exact constraints; this table is a quick reference.

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
