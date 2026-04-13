PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'registered',
  created_at DATETIME NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS perfumes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  description TEXT,
  image_url TEXT,
  top_notes TEXT,
  heart_notes TEXT,
  base_notes TEXT,
  accords TEXT,
  rating REAL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS about_info (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  heading TEXT UNIQUE NOT NULL,
  intro TEXT,
  details TEXT,
  features TEXT,
  audience TEXT,
  benefits TEXT,
  stat_1_value TEXT,
  stat_1_label TEXT,
  stat_2_value TEXT,
  stat_2_label TEXT,
  stat_3_value TEXT,
  stat_3_label TEXT,
  created_at DATETIME NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS inventory (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  perfume_id INTEGER NOT NULL,
  available_quantity INTEGER NOT NULL DEFAULT 0,
  damaged_quantity INTEGER NOT NULL DEFAULT 0,
  expiration_date DATE,
  last_updated DATETIME NOT NULL DEFAULT (datetime('now')),
  UNIQUE(perfume_id),
  FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS purchase_lists (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  created_by INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  owner_note TEXT,
  created_at DATETIME NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS purchase_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  purchase_list_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY (purchase_list_id) REFERENCES purchase_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  message TEXT,
  created_at DATETIME NOT NULL DEFAULT (datetime('now'))
);

INSERT OR IGNORE INTO users (username, email, password_hash, role) VALUES
('owner', 'owner@labscentique.local', '$2y$12$TvDmcXo14lu/uCsGTO0Jt.ZvyWTnQA.46wscmpW2D1vSFeVBHzXcW', 'owner'),
('staff', 'staff@labscentique.local', '$2y$12$m08DBY9eI/hgQgUFalFDf.6NiERwH8RqkimLDc52K74vxk2J5CM.K', 'staff');

INSERT OR IGNORE INTO perfumes (name, description, image_url, top_notes, heart_notes, base_notes, accords, rating) VALUES
('Khamrah', 'A rich oriental fragrance blending saffron, rose, and amber for a warm, sensual experience.', 'https://fimgs.net/mdimg/perfume/375x500.75805.jpg', 'Saffron, Rose, Bergamot', 'Amber, Patchouli, Jasmine', 'Vanilla, Musk, Sandalwood', 'Oriental, Floral, Warm', 4.2),
('Liquid Brun', 'A bold, masculine scent with leather and tobacco notes, perfect for evening wear.', 'https://fimgs.net/mdimg/perfume/375x500.94713.jpg', 'Leather, Tobacco, Pepper', 'Vetiver, Cedar', 'Amber, Musk', 'Leather, Smoky, Masculine', 4.1),
('Emporio Armani Stronger With You Intensely', 'An intense woody aromatic fragrance with citrus and spice accents.', 'https://fimgs.net/mdimg/perfume/375x500.52802.jpg', 'Grapefruit, Pepper, Ginger', 'Jasmine, Rosewood', 'Patchouli, Amber, Leather', 'Woody, Aromatic, Spicy', 4.3),
('Khamrah Qahwa', 'A coffee-infused oriental with warm spices and sweet vanilla.', 'https://fimgs.net/mdimg/perfume/375x500.88175.jpg', 'Coffee, Saffron, Cardamom', 'Rose, Amber', 'Vanilla, Patchouli', 'Oriental, Spicy, Sweet', 4.0),
('Le Male Elixir', 'An elixir version of the classic Le Male, with enhanced lavender and mint.', 'https://fimgs.net/mdimg/perfume/375x500.81642.jpg', 'Lavender, Mint, Cardamom', 'Orange Blossom, Cinnamon', 'Vanilla, Tonka Bean, Sandalwood', 'Aromatic, Fresh, Sweet', 4.4),
('Imagination', 'A floral fruity fragrance with pear and rose, evoking creativity.', 'https://fimgs.net/mdimg/perfume/375x500.62251.jpg', 'Pear, Bergamot', 'Rose, Jasmine', 'Vanilla, Musk', 'Floral, Fruity, Sweet', 4.5),
('Hawas Ice', 'A fresh aquatic with icy mint and citrus for a cool vibe.', 'https://fimgs.net/mdimg/perfume/375x500.89050.jpg', 'Mint, Lemon, Apple', 'Jasmine, Rose', 'Amber, Musk', 'Aquatic, Fresh, Citrus', 4.0),
('Millesime Imperial', 'A luxurious chypre with citrus, floral, and woody notes.', 'https://fimgs.net/mdimg/perfume/375x500.466.jpg', 'Bergamot, Mandarin', 'Jasmine, Rose', 'Patchouli, Sandalwood, Amber', 'Chypre, Floral, Woody', 4.6),
('Zenith Deep', 'A deep woody oriental with incense and spices.', 'https://fimgs.net/mdimg/perfume/375x500.120870.jpg', 'Incense, Pepper, Saffron', 'Rose, Amber', 'Patchouli, Vanilla', 'Oriental, Woody, Spicy', 4.2),
('Roberto Cavalli Nero Assoluto', 'An intense leather fragrance with tobacco and spice.', 'https://fimgs.net/mdimg/perfume/375x500.18833.jpg', 'Tobacco, Pepper, Saffron', 'Leather, Rose', 'Amber, Patchouli', 'Leather, Spicy, Oriental', 4.3);

INSERT OR IGNORE INTO about_info (heading, intro, details, features, audience, benefits, stat_1_value, stat_1_label, stat_2_value, stat_2_label, stat_3_value, stat_3_label) VALUES
('Finding the perfect fragrance should not be overwhelming—it should be inspiring.', 'LabScentique is a web-based platform designed to make perfume discovery simple, personal, and enjoyable, while also helping businesses manage their inventory with ease.', 'We combine fragrance passion with business precision—helping users find their signature scent while empowering owners to make smarter decisions. LabScentique is not just a platform; it is your partner in perfume discovery and management.', 'Personalized Recommendations, Community & Learning, Smart Inventory Management', 'Whether you are a beginner exploring perfumes, a collector seeking rare notes, or a business owner managing daily operations, LabScentique brings everything together in one seamless experience.', 'We combine fragrance passion with business precision—helping users find their signature scent while empowering owners to make smarter decisions. LabScentique is not just a platform; it is your partner in perfume discovery and management.', '50+', 'Unique formulations', '1000+', 'Satisfied customers', '5★', 'Average rating');

INSERT OR IGNORE INTO inventory (perfume_id, available_quantity, damaged_quantity, expiration_date)
SELECT id, 25, 0, date('now', '+120 days') FROM perfumes;

