# Scenes

_the universal online hierarchical album_

-----------------------------------------

**Scenes** is an online hierarchical album management system, which organizes assets into collections and displays them in a linear format with or without descriptions and other metadata. It is designed mainly with photography in mind, but can also be applied to almost any other use case involving listing and describing files and data.

_Scenes_ is roughly inspired by Dave Flack's [World Photo Album](https://www.qsl.net/ah6hy/photos.html), a simple, legacy-friendly website that presents photos he has taken during his travels in a linear format with occasional image descriptions or sections. Like Flack's site, _Scenes_ aims to present content in a simple and compatible manner, but also in a way that is more consistent across pages, flexible and easy to maintain on the backend.

_Scenes_ emphasizes maximal compatibility with legacy clients, with a front-end built with semantic but legacy-friendly HTML and no requirements for stylesheets or JavaScript unless desired and configured as such by the album owner.

_Scenes_ can be used as a standalone website, but also can be deployed in a subdirectory in your existing site, in case you are hosting other applications on the same system.

## Theory of operation

Fundamentally, _Scenes_ operates on collections of assets organized in a hierarchical fashion. A collection can have multiple children, as well as multiple parents, and any number of assets within it. Collections can also take the form of empty "roots" with only links and metadata, that can serve to aggregate and further categorize child collections. Every _Scenes_ site begins with a master root collection which cannot be deleted, and from which all other collections descend. The default root collection can contain assets and metadata of its own, and serves as the site's home page in practical terms. In addition to the default root collection, Scenes sites also initialize with a hidden `assets` collection where newly added but unassigned assets are aggregated. Assets within collections posess their own metadata context unique to that collection, meaning a single asset can have a unique description for each collection it is placed in, as well as a default description (defined in its `assets` collection membership).

Assets and collections are managed through an authenticated web interface. Album owners who do not wish to use SSL (for legacy reasons) can reduce risk through optional IP whitelisting that can allow them to limit authentication only to specific clients (or a range of clients) on a local network.

To allow for integrating content in albums in other projects, _Scenes_ implements a basic API that can be used to get individual assets or entire collections.

## Implementation

_Scenes_ is designed as a simple PHP/SQLite-backed MVC application with this essential directory structure:
- `pub`: Public site, API endpoints and related files
- `sys`: System files (models, views, controllers and related)
- `config`: Site configuration files
- `data`: Site data (created automatically to host assets and the system database)
