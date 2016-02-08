#Indexing Console App

## Installation

* Install [Composer](https://getcomposer.org)
* Check out this repo
* Copy `parameters.yml.orig` to `parameters.yml` and change settings.
* Run `composer install` to install dependencies.

## Symlinks to enable wiki integration

Symlink your

* `LocalSettings.php`
* `includes`

to the root of this project.

## Set environment var for the wiki (used in WebStart.php)

export MW_INSTALL_PATH=<the path to your specific wiki dir>

e.g.: export MW_INSTALL_PATH=~/wikifarm/wikis/deltaexpertise/wiki
