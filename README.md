# Scenes

_the universal online hierarchical data album_

-----------------------------------------

**Scenes** is an online hierarchical album management system, which organizes assets into collections and displays them in various formats with or without descriptions and other metadata. It is designed mainly with photography in mind, but can also be applied to almost any other use case involving listing and describing arbitrary files and data.

_Scenes_ is roughly inspired by Dave Flack's [World Photo Album](https://www.qsl.net/ah6hy/photos.html), a personal favorite of mine with a simple, legacy-friendly layout presenting interesting photos in a linear manner, with occasional image descriptions or sections. Like Flack's site, _Scenes_ aims to present content in a simple and compatible manner, but also in a way that is more consistent across pages, flexible and easy to maintain on the backend.

_Scenes_ emphasizes maximal compatibility with legacy clients, with a front-end built with semantic but legacy-friendly HTML and no requirements for stylesheets or JavaScript unless desired and configured as such by the album owner.

_Scenes_ can be used as a standalone website, but also can be deployed in a subdirectory in your existing site, in case you are hosting other applications on the same system.

## Using this repository

Scenes is a PHP application intended to be hosted by an Apache web server with a simple SQLite database on the backend, meaning you will need both on your target system if you intend to deploy the application as-is. Use of other web servers or databases will require you to do your own configuration and model implementation.

Quick start:
- Install `php`, `apache2` (with `mod_php`) and `sqlite3` on the target server
- Verify `mod_php` is enabled within Apache using `apachectl -M` (if `mod_php` is not enabled, search for it using `a2enmod -l` and then enabling it with `a2enmod <name>`)
- Allow `.htaccess` overrides in Apache by editing the document root directory configuration in `httpd.conf` or `default-server.conf`, depending on your implementation
- Clone the repository to your document root directory 
