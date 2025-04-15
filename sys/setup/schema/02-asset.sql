-- Asset table
CREATE TABLE assets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL, -- Original filename
    filepath TEXT NOT NULL, -- Path to the asset on the filesystem
    filetype TEXT NOT NULL, -- MIME type or file extension
    filesize INTEGER, -- Size in bytes
    checksum TEXT, -- For integrity checking and import/export
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for faster lookups
CREATE INDEX idx_assets_filename ON assets(filename);
CREATE INDEX idx_assets_filetype ON assets(filetype);
CREATE INDEX idx_assets_checksum ON assets(checksum);

-- Trigger to update the 'updated_at' timestamp when an asset is modified
CREATE TRIGGER update_assets_timestamp 
AFTER UPDATE ON assets
FOR EACH ROW
BEGIN
    UPDATE assets SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Asset-Collection association table with self-referential groups
CREATE TABLE asset_collection_membership (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_id INTEGER NOT NULL,
    collection_id INTEGER NOT NULL,
    display_name TEXT, -- Optional override for display purposes
    description TEXT, -- Context-specific description
    group_leader BOOLEAN DEFAULT 0, -- Flag to identify the record that defines the group
    group_id INTEGER, -- References another asset_collection_assignment that acts as the group
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(asset_id, collection_id), -- Each asset can only be in a collection once
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES asset_collection_assignments(id) ON DELETE CASCADE
);

-- Indexes for faster lookups
CREATE INDEX idx_asset_collection_asset ON asset_collection_assignments(asset_id);
CREATE INDEX idx_asset_collection_collection ON asset_collection_assignments(collection_id);
CREATE INDEX idx_asset_collection_group ON asset_collection_assignments(group_id);

-- Trigger to update the 'updated_at' timestamp when an assignment is modified
CREATE TRIGGER update_asset_collection_assignments_timestamp 
AFTER UPDATE ON asset_collection_assignments
FOR EACH ROW
BEGIN
    UPDATE asset_collection_assignments SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Asset Group Display Modes table
CREATE TABLE asset_group_display_modes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Initial asset group display modes
INSERT INTO asset_group_display_modes (name, description) VALUES 
('linear', 'Display assets in a linear sequence'),
('side-by-side', 'Display assets horizontally adjacent to each other'),
('grid', 'Display assets in a grid layout');

-- Join table for asset groups and display modes
CREATE TABLE asset_group_display_mode_configuration (
    group_id INTEGER NOT NULL, -- References the group leader in asset_collection_assignments
    display_mode_id INTEGER NOT NULL,
    composite BOOLEAN NOT NULL DEFAULT 0, -- compose collection into a single image with an image map
    UNIQUE(group_id, display_mode_id),
    PRIMARY KEY (group_id, display_mode_id),
    FOREIGN KEY (group_id) REFERENCES asset_collection_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (display_mode_id) REFERENCES asset_group_display_modes(id) ON DELETE RESTRICT
);
