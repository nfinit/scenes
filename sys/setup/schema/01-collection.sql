-- Collection table
CREATE TABLE collections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    title TEXT, -- Optional title that will override collection name on view
    description TEXT,
    protected BOOLEAN NOT NULL DEFAULT 0, -- require authentication if protected 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for faster lookups by slug
CREATE INDEX idx_collections_slug ON collections(slug);
CREATE INDEX idx_collections_name ON collections(name); 

-- Display modes table
CREATE TABLE collection_display_modes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Initial display modes
INSERT INTO collection_display_modes (name, description) VALUES 
('grid', 'Display assets in a grid layout'),
('linear', 'Display assets in a linear layout with descriptions'),
('tabular', 'Display assets in a table format');

-- Join table for collections and display modes
CREATE TABLE collection_display_mode_configuration (
    collection_id INTEGER NOT NULL,
    display_mode_id INTEGER NOT NULL,
		UNIQUE(collection_id,display_mode_id),
    PRIMARY KEY (collection_id, display_mode_id),
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (display_mode_id) REFERENCES display_modes(id) ON DELETE RESTRICT
);

-- Trigger to update the 'updated_at' timestamp when a collection is modified
CREATE TRIGGER update_collections_timestamp 
AFTER UPDATE ON collections
FOR EACH ROW
BEGIN
    UPDATE collections SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Collection relationships table
CREATE TABLE collection_relationships (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- Adding a primary key for this table
    parent_id INTEGER NOT NULL,
    child_id INTEGER NOT NULL,
    show_metadata BOOLEAN DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    UNIQUE(parent_id, child_id), -- Ensure each parent-child relationship is unique
    FOREIGN KEY (parent_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES collections(id) ON DELETE CASCADE
);

-- Relationship display modes table
CREATE TABLE relationship_display_modes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Initialize with the basic relationship display modes
INSERT INTO relationship_display_modes (name, description) VALUES 
('linked', 'Display as a text link to the collection'),
('embedded', 'Display the collection contents within the parent');

-- Join table for collection relationships and display modes
CREATE TABLE relationship_display_mode_configuration (
    relationship_id INTEGER NOT NULL,
    display_mode_id INTEGER NOT NULL,
		UNIQUE(relationship_id,display_mode_id),
    PRIMARY KEY (relationship_id, display_mode_id),
    FOREIGN KEY (relationship_id) REFERENCES collection_relationships(id) ON DELETE CASCADE,
    FOREIGN KEY (display_mode_id) REFERENCES relationship_display_modes(id) ON DELETE RESTRICT
);

-- Indexes for faster lookups
CREATE INDEX idx_collection_relationships_parent ON collection_relationships(parent_id);
CREATE INDEX idx_collection_relationships_child ON collection_relationships(child_id);
