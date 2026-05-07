<?php
/**
 * Database Migration Utility
 * Converts database configuration from SQLite to PostgreSQL
 * Usage: php src/migrate-database-config.php
 */

declare(strict_types=1);

echo "LabScentique Database Configuration Helper\n";
echo "===========================================\n\n";

$migrationMode = $argv[1] ?? 'info';

switch ($migrationMode) {
    case 'generate-postgres-schema':
        generatePostgresSchema();
        break;
    case 'info':
    default:
        showInfo();
        break;
}

function showInfo() {
    echo "Current Configuration:\n";
    echo "---------------------\n";
    
    if (file_exists(__DIR__ . '/../config/config.php')) {
        echo "✓ config/config.php exists\n";
    } else {
        echo "✗ config/config.php NOT found\n";
    }
    
    if (file_exists(__DIR__ . '/../.env')) {
        echo "✓ .env file exists\n";
    } else {
        echo "✗ .env file NOT found\n";
    }
    
    if (file_exists(__DIR__ . '/../labscentique.db')) {
        echo "✓ SQLite database exists (labscentique.db)\n";
        $size = filesize(__DIR__ . '/../labscentique.db');
        echo "  Size: " . round($size / 1024, 2) . " KB\n";
    } else {
        echo "✗ SQLite database NOT found\n";
    }
    
    echo "\nAvailable Commands:\n";
    echo "-------------------\n";
    echo "php src/migrate-database-config.php generate-postgres-schema\n";
    echo "  - Outputs PostgreSQL schema SQL\n\n";
}

function generatePostgresSchema() {
    echo "PostgreSQL Schema SQL\n";
    echo "====================\n\n";
    
    $postgresSchema = <<<'SQL'
-- PostgreSQL Schema for LabScentique
-- Convert from SQLite to PostgreSQL

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Users Table
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  full_name VARCHAR(255),
  bio TEXT,
  profile_picture_url TEXT,
  role VARCHAR(50) NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Perfumes Table
CREATE TABLE perfumes (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  description TEXT,
  image_url TEXT,
  top_notes TEXT,
  heart_notes TEXT,
  base_notes TEXT,
  accords TEXT,
  rating DECIMAL(3, 1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT NOW()
);

-- About Info Table
CREATE TABLE about_info (
  id SERIAL PRIMARY KEY,
  heading VARCHAR(255) UNIQUE NOT NULL,
  intro TEXT,
  details TEXT,
  features TEXT,
  audience TEXT,
  benefits TEXT,
  stat_1_value VARCHAR(255),
  stat_1_label VARCHAR(255),
  stat_2_value VARCHAR(255),
  stat_2_label VARCHAR(255),
  stat_3_value VARCHAR(255),
  stat_3_label VARCHAR(255),
  created_at TIMESTAMP DEFAULT NOW()
);

-- Inventory Table
CREATE TABLE inventory (
  id SERIAL PRIMARY KEY,
  perfume_id INTEGER NOT NULL,
  available_quantity INTEGER DEFAULT 0,
  damaged_quantity INTEGER DEFAULT 0,
  expiration_date DATE,
  last_updated TIMESTAMP DEFAULT NOW(),
  UNIQUE(perfume_id),
  CONSTRAINT fk_inventory_perfume FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- Purchase Lists Table
CREATE TABLE purchase_lists (
  id SERIAL PRIMARY KEY,
  staff_id INTEGER NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  owner_note TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  approved_at TIMESTAMP,
  CONSTRAINT fk_purchase_lists_staff FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Purchase List Items Table
CREATE TABLE purchase_list_items (
  id SERIAL PRIMARY KEY,
  purchase_list_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  quantity INTEGER DEFAULT 1,
  CONSTRAINT fk_purchase_items_list FOREIGN KEY (purchase_list_id) REFERENCES purchase_lists(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchase_items_perfume FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- Contacts Table
CREATE TABLE contacts (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  message TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Inventory Access Table
CREATE TABLE inventory_access (
  id SERIAL PRIMARY KEY,
  staff_id INTEGER NOT NULL,
  inventory_id INTEGER NOT NULL,
  access_level VARCHAR(50) DEFAULT 'manage',
  granted_by INTEGER NOT NULL,
  granted_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(staff_id, inventory_id),
  CONSTRAINT fk_inv_access_staff FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_access_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_access_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Inventory Audit Table
CREATE TABLE inventory_audit (
  id SERIAL PRIMARY KEY,
  inventory_id INTEGER NOT NULL,
  changed_by INTEGER NOT NULL,
  change_type VARCHAR(50),
  old_value TEXT,
  new_value TEXT,
  timestamp TIMESTAMP DEFAULT NOW(),
  CONSTRAINT fk_audit_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
  CONSTRAINT fk_audit_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Customer Purchases Table
CREATE TABLE customer_purchases (
  id SERIAL PRIMARY KEY,
  customer_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  quantity INTEGER DEFAULT 1,
  purchase_date TIMESTAMP DEFAULT NOW(),
  CONSTRAINT fk_cust_purchases_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cust_purchases_perfume FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- User Favorites Table
CREATE TABLE user_favorites (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  added_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(user_id, perfume_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_perfume FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- Create indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_perfumes_name ON perfumes(name);
CREATE INDEX idx_inventory_perfume_id ON inventory(perfume_id);
CREATE INDEX idx_purchase_lists_staff_id ON purchase_lists(staff_id);
CREATE INDEX idx_purchase_lists_status ON purchase_lists(status);
CREATE INDEX idx_contacts_email ON contacts(email);
CREATE INDEX idx_inventory_access_staff_id ON inventory_access(staff_id);
CREATE INDEX idx_inventory_audit_inventory_id ON inventory_audit(inventory_id);
CREATE INDEX idx_customer_purchases_customer_id ON customer_purchases(customer_id);
CREATE INDEX idx_user_favorites_user_id ON user_favorites(user_id);

-- Seed data (optional - add initial perfumes)
INSERT INTO perfumes (name, description, image_url, top_notes, heart_notes, base_notes, accords, rating)
VALUES
  ('Khamrah', 'A rich oriental fragrance blending saffron, rose, and amber for a warm, sensual experience.',
   'https://fimgs.net/mdimg/perfume/375x500.75805.jpg', 'Saffron, Rose, Bergamot', 'Amber, Patchouli, Jasmine', 'Vanilla, Musk, Sandalwood', 'Oriental, Floral, Warm', 4.2),
  ('Le Male Elixir', 'An elixir version of the classic Le Male, with enhanced lavender and mint.',
   'https://fimgs.net/mdimg/perfume/375x500.81642.jpg', 'Lavender, Mint, Cardamom', 'Orange Blossom, Cinnamon', 'Vanilla, Tonka Bean, Sandalwood', 'Aromatic, Fresh, Sweet', 4.4),
  ('Millésime Impérial', 'A luxurious chypre with citrus, floral, and woody notes.',
   'https://fimgs.net/mdimg/perfume/375x500.466.jpg', 'Bergamot, Mandarin', 'Jasmine, Rose', 'Patchouli, Sandalwood, Amber', 'Chypre, Floral, Woody', 4.6)
ON CONFLICT DO NOTHING;

-- Create inventory for each perfume
INSERT INTO inventory (perfume_id, available_quantity, damaged_quantity)
SELECT id, 25, 0 FROM perfumes
ON CONFLICT (perfume_id) DO NOTHING;

-- Create seed user (optional)
-- Password: AdminPassword123
INSERT INTO users (username, email, password_hash, full_name, role)
VALUES ('admin', 'admin@labscentique.com', '$2y$10$lL5/AY3jVbZ.8c.f/bKfkOIg5fT5lZ1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z1Z', 'Administrator', 'owner')
ON CONFLICT (username) DO NOTHING;

SQL;
    
    echo $postgresSchema;
    echo "\n\nTo use this schema:\n";
    echo "1. Create a PostgreSQL database\n";
    echo "2. Run this SQL against your database\n";
    echo "3. Update config/config.php to use PostgreSQL\n";
    echo "4. Update .env with DATABASE_URL\n";
}
?>
