#Indexing Console App
The Indexing Console App is a CLI (using elasticsearch) to index the (semantic) wiki content of the Expertise Management wiki systems.

## Installation

* Install [Composer](https://getcomposer.org)
* Check out this repo
* Run `composer install` to install dependencies.
* [if not asked by composer] Copy `parameters.yml.dist` to `parameters.yml` and change to the correct settings.
* Symlink the `includes` folder of your mediawiki core installation with name `includes` in the root of this project.
* Set bash environment var for the specific wiki to be indexed (the var is used in includes/WebStart.php):

export MW_INSTALL_PATH=<the path to your specific wiki installation>

e.g.: export MW_INSTALL_PATH=~/wikifarm/wikis/deltaexpertise/wiki

NB: GlobalLocalSettings.php in our wikifarm determines the correct config from this setting. 
