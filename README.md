# Hero Labs Dropbox Display
This app allows you to display Hero Labs portfolios stored in your dropbox directly in the browser.

## Using the app

:exclamation: There is currently no productive instance of this app. You will have to run it on your own server. See
Setup below.

When first accessing the app, you will be redirected to Dropbox to grant access to your folders. All Hero Labs portfolios
found in your Dropbox will then be listed. Select one and the page will refresh to allow you to select a character. After 
selection, the characters stat block will be shown on the page.

## Setup
Clone the repository from GitHub. The required library [DropPHP]() is included as a submodule, so run 

    $ git submodule sync
    $ git submodule update
to get the correct version of the library.

To run the app, copy the sample config file `config.sample.php` to `config.php`. You have to 
[create an app](https://www.dropbox.com/developers/apps/create) in Dropbox and enter the app key and secret
into your configuration file.
