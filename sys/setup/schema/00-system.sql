-- Users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL, -- Store bcrypt/Argon2 hash, not plaintext passwords
    email TEXT UNIQUE,
    full_name TEXT,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for faster user lookups
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

-- Trigger to update the 'updated_at' timestamp when a user is modified
CREATE TRIGGER update_users_timestamp 
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

-- Roles table
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Initialize with base roles
INSERT INTO roles (name, description) VALUES 
('administrator', 'Full system access with all privileges'),
('user', 'Basic access to view public and specifically granted protected collections');

-- User-Role association table
CREATE TABLE user_roles (
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Create default administrator account
-- Note: This is just for initialization; the password should be changed immediately
INSERT INTO users (
    username,
    password_hash,
    full_name,
    active
) VALUES (
    'scenes',
    'uninitialized', -- This should be replaced during setup
    'Scenes Administrator',
    1
);

-- Assign administrator role to default admin user
INSERT INTO user_roles (
    user_id,
    role_id
) VALUES (
    1, -- Default admin user (assuming ID 1)
    (SELECT id FROM roles WHERE name = 'administrator')
);

-- IP whitelist for legacy administration or enhanced security
CREATE TABLE ip_whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL, -- Can be single IP or CIDR notation for range
    description TEXT,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ip_whitelist_ip_address ON ip_whitelist(ip_address);

-- Trigger to update the 'updated_at' timestamp when whitelist entry is modified
CREATE TRIGGER update_ip_whitelist_timestamp 
AFTER UPDATE ON ip_whitelist
FOR EACH ROW
BEGIN
    UPDATE ip_whitelist SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;
