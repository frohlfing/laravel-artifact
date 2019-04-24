# Command to create a local composer repository of type "artifact" for Laravel 5.7

This package provides following artisan commands:

- artifact:create - Create archives for packages that the project uses.
- artifact:remove - Remove all archives for packages that the project no longer uses.

## Installation
    
I have not yet deployed this package to Packagist, the Composers default package archive. Therefore, you must tell 
Composer where the package is. To do this, add the following lines into your `composer.json`:

    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/frohlfing/laravel-artifact.git"
        }
    ],

Download this package by running the following command:

    composer require frohlfing/laravel-artifact:1.57.*@dev
    
You need to publish the config file for this package. This will add the file `config/laravel-artifact.php`, where you 
can configure this package:

    php artisan vendor:publish --provider="FRohlfing\Artifact\ArtifactServiceProvider" --tag=config
    
## Usage

Add the following lines into your `composer.json`:

    "repositories": [
        {
            "type": "artifact",
            "url": "path/to/directory/with/archives/"
        }, 
        {
            "packagist": false
        }
    ],      