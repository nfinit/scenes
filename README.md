**scenes** is a work-in-progress online hierarchical photo album management system. This project is being developed mainly to manage travel photography, but can reasonably be applied to any other subject.

Work on _scenes_ is very roughly inspired by Dave Flack's [World Photo Album](https://www.qsl.net/ah6hy/photos.html), one of my favorite travel websites for the volume of interesting content it presents in a simple, brief, legacy-friendly format. Like Flack's site, _scenes_ aims to present its content in a simple and compatible manner, but also in a way that is consistent, flexible and easy to maintain on the backend.

At a fundamental level, _scenes_ operates on collections of assets. These collections can be completely empty with only metadata, or include many assets. Collections are further organized in a hierarchical fashion, with each collection capable of having multiple "children" while itself being the "child" of multiple collections. Empty collections in this scheme are best understood as "roots" that can be used to aggregate child collections.

All assets in _scenes_ must be associated with a collection, and if unspecified they are by default associated with a master (root) collection where they can then be interchanged with others at will. A given asset can belong to multiple collections, with its own metadata (descriptions, etc) in each one.

In line with most of my other projects, _scenes_ also emphasizes maximal compatibility with legacy clients through use of simple, semantic HTML that is also highly stylable.

Assets can be either manually added or uploaded through the web. Site owners who do not wish to use SSL but still wish to administrate the site from a browser can instead use client whitelists to allow only their local network to access administrative functionality.

To allow for dynamic sharing of album content, _scenes_ implements a simple API for getting content from collections or aggregates of collections.
