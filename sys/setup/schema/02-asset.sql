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

-- Asset-Collection membership table 
CREATE TABLE asset_collection_membership (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_id INTEGER NOT NULL,
    collection_id INTEGER NOT NULL,
    display_name TEXT, -- Optional override for display purposes
    description TEXT, -- Context-specific description
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(asset_id, collection_id), -- Each asset can only be in a collection once
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
);

-- Indexes for faster lookups
CREATE INDEX idx_asset_collection_asset ON asset_collection_membership(asset_id);
CREATE INDEX idx_asset_collection_collection ON asset_collection_membership(collection_id);

-- Trigger to update the 'updated_at' timestamp when an assignment is modified
CREATE TRIGGER update_asset_collection_membership_timestamp 
AFTER UPDATE ON asset_collection_membership
FOR EACH ROW
BEGIN
    UPDATE asset_collection_membership SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Asset groups table
CREATE TABLE asset_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    collection_id INTEGER NOT NULL,
    name TEXT, -- Optional name/title for the group
    description TEXT,
    -- Sort order in the collection will be determined by the lowest member value
    active BOOLEAN DEFAULT 1, -- Flag to easily enable/disable the entire group
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
);

-- Index for faster lookups
CREATE INDEX idx_asset_groups_collection ON asset_groups(collection_id);

-- Trigger to update the 'updated_at' timestamp when a group is modified
CREATE TRIGGER update_asset_groups_timestamp 
AFTER UPDATE ON asset_groups
FOR EACH ROW
BEGIN
    UPDATE asset_groups SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Asset group membership table - connects assets in a collection to groups
CREATE TABLE asset_group_membership (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    membership_id INTEGER NOT NULL, -- References asset_collection_membership
    sort_order INTEGER DEFAULT 0, -- Within the group, NOT the collection
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(membership_id), -- Each asset-collection membership can only be in one group
    FOREIGN KEY (group_id) REFERENCES asset_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (membership_id) REFERENCES asset_collection_membership(id) ON DELETE CASCADE
);

-- Indexes for faster lookups
CREATE INDEX idx_asset_group_membership_group ON asset_group_membership(group_id);
CREATE INDEX idx_asset_group_membership_membership ON asset_group_membership(membership_id);

-- Trigger to update the 'updated_at' timestamp when a group membership is modified
CREATE TRIGGER update_asset_group_membership_timestamp 
AFTER UPDATE ON asset_group_membership
FOR EACH ROW
BEGIN
    UPDATE asset_group_membership SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
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
    group_id INTEGER NOT NULL, -- References the asset_groups table (not membership)
    display_mode_id INTEGER NOT NULL,
    composite BOOLEAN NOT NULL DEFAULT 0, -- compose group into a single image with an image map
    UNIQUE(group_id, display_mode_id),
    PRIMARY KEY (group_id, display_mode_id),
    FOREIGN KEY (group_id) REFERENCES asset_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (display_mode_id) REFERENCES asset_group_display_modes(id) ON DELETE RESTRICT
);
