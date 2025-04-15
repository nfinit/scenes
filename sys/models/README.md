Scenes data model
-----------------

This directory contains system definitions for the Scenes data model. Scenes
is designed to be backed by an SQLite database for ease and simplicity, with
the a highly normalized database where each table contains only the most
essential fields for its purpose. This ensures that new features, should
they be needed, can be added with new tables attached to the core structures.

## Structures

### Collections

The _collection_ serves to aggregate and organize assets in some manner. 
In addition to having any number of associated assets, a collection can have 
any number of child collections as well as any number of parents.

In addition to their assets and links, collections have their own metadata:
- A unique, fixed sequential ID for internal identification
- A unique "slug" for use in URLs, that can be changed at will
- A proper text name for display in the application
- A description which can contain any arbitrary text
- An optional page title that overrides the name when the collection is viewed
- An optional title graphic asset that can display standalone or alongside the
  collection name/title when it is viewed 

Collections can have three effective visibility states:
- **Public** collections are linked to the root (or some other) collection
  and can therefore be navigated to within a Scenes site normally
- **Unlisted** collections are not linked to the root collection, and thus can
  only be viewed by someone who has a link to it (or knows its slug)
- **Hidden** collections can be solitary or linked to the root, but are
  explicitly flagged within the system to only be viewable by an authenticated
  user

Collections link together using a separate join table to allow for arbitrary
numbers of children and parents. In addition to this, collection links can also
force a sort order (an ascending integer value) and specify a display mode:
- **Linked** collections show only as text links when their parent is viewed
- **Embedded** collections display their contents within the parent view as if
  they were part of the parent collection itself, optionally with or without
  its own collection metadata.

Collections themselves also have a display mode that will be respected when
embedded in other collections, for example a photo album may display as a grid
or a proper gallery of photos displayed in a linear fashion with a description
for each one, while an album of binary files may simply want to display them in
a tabular format.

Collections can be cloned, either by metadata only or also including their
asset links. Likewise, asset links can be copied between existing collections.

Because the collection is the primary means through which assets are organized
and displayed, it can be considered the most fundamental unit of a Scenes
deployment. The most basic Scenes site can consist of only a root collection
with metadata and no other assets. A user so inclined could even use Scenes to
host a completely static HTML site by using empty collections with content only
in their descriptions.

A basic Scenes deployment initilizes with two core collections:
- The **root** collection, a system collection which serves as the "home" page 
  of the Scenes application, and of which all other public collections are 
  children of
- The **assets** collection, a hidden system collection to which all assets are 
  linked when uploaded

### Assets

Alongside collections, _assets_ are the other key fundamental structure of the
Scenes model, representing a file of any format. Though Scenes is primarily
intended for use as a photo album, it can also be used to aggregate and
organize other kinds of files as well. Assets are stored by Scenes in a common 
pool that assigns them a unique ID, records their location on the host and also
records a checksum to support export and import operations.

Any asset can belong to any number of collections, and have their own unique
description and display name (along with any other client-facing metadata) 
within that collection context.

Within a collection context, assets can be further grouped together in a sort
of "sub-collection" such that they will be displayed together with common
metadata when the collection is viewed as if they were a single asset. This
group structure includes:
- A sort order, which will either be `NULL` or otherwise the smallest value of
  any of the grouped assets.
- A view mode specifier that determines the way the assets will be presented,
  for example in standard linear fashion, side-by-side, or even stitched 
  together in an image map.

## Implementation

Scenes' data implementation takes a highly normalized approach with separate 
tables for each core entity and its relationships, with the goal of allowing 
for a simple but extensible system, where new functionality can be added
without the overhead of migration or transformation of existing data. 
