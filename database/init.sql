PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  full_name TEXT,
  bio TEXT,
  profile_picture_url TEXT,
  role TEXT NOT NULL DEFAULT 'registered',
  created_at DATETIME NOT NULL DEFAULT (datetime('now')),
  updated_at DATETIME NOT NULL DEFAULT (datetime('now'))
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
  staff_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  owner_note TEXT,
  created_at DATETIME NOT NULL DEFAULT (datetime('now')),
  approved_at DATETIME,
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS purchase_list_items (
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

-- Inventory Access Control: Track which staff members can manage specific inventory
CREATE TABLE IF NOT EXISTS inventory_access (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  staff_id INTEGER NOT NULL,
  inventory_id INTEGER NOT NULL,
  access_level TEXT NOT NULL DEFAULT 'manage',
  -- access_level: 'view' (read-only), 'manage' (can update quantity)
  granted_by INTEGER NOT NULL,
  -- granted_by: owner user_id who authorized this access
  granted_at DATETIME NOT NULL DEFAULT (datetime('now')),
  UNIQUE(staff_id, inventory_id),
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
  FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Audit Log: Track inventory changes for compliance
CREATE TABLE IF NOT EXISTS inventory_audit (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  inventory_id INTEGER NOT NULL,
  changed_by INTEGER NOT NULL,
  prev_available INTEGER,
  new_available INTEGER,
  prev_damaged INTEGER,
  new_damaged INTEGER,
  reason TEXT,
  changed_at DATETIME NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Customer Purchases: Track customer perfume purchases
CREATE TABLE IF NOT EXISTS customer_purchases (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  purchase_date DATETIME NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- User Favorites: Track perfumes users have favorited
CREATE TABLE IF NOT EXISTS user_favorites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  perfume_id INTEGER NOT NULL,
  added_at DATETIME NOT NULL DEFAULT (datetime('now')),
  UNIQUE(user_id, perfume_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
);

-- USER ROLES & ACCESS LEVELS:
-- 'registered': Regular user - browse perfumes, submit contact forms
-- 'staff': Staff member - manage inventory, create purchase lists
-- 'owner': Owner - approve/reject purchase lists, manage staff access, view reports
-- 'admin': System admin - manage users and full system access

INSERT OR IGNORE INTO users (username, email, password_hash, role) VALUES
-- Primary Accounts
('owner', 'owner@labscentique.local', '$2y$12$L24GOG2LEeJtfvEUvSiMR.xpYw.WiAeR/zLMitziXafwAr9neaSuy', 'owner'),
('staff', 'staff@labscentique.local', '$2y$12$LZTDlvGgeOx.LsG6uQdjKO4HYwqOCVgZ9hLAXX79aVE6p.B5PgwBe', 'staff'),
-- Additional Owner Accounts (for backup/secondary ownership)
('manager', 'manager@labscentique.local', '$2y$12$0V.E3/tmYfJQoW1cyCYuJO4tpp3uRYNpfVs3kfFk/yKS7yJsGVs1.', 'owner'),
-- Additional Staff Accounts (different departments/shifts)
('staff_warehouse', 'warehouse@labscentique.local', '$2y$12$p8ZEWp9jfQkyhRysc0lWX.myXJEZ8SJf.UQIaqHNv/sFEWy/GDRHC', 'staff'),
('staff_quality', 'quality@labscentique.local', '$2y$12$QfTeNgzbSiXqaOA66Atde.8if2wdb8/iyXNb8qvDEzrdtMwUUqy9q', 'staff'),
-- Test User (registered, cannot access dashboard)
('guest_user', 'guest@labscentique.local', '$2y$12$GKYSndddLM8/UHvdhhpMuOIdFuBa.sf9r4HO5vpy0EmdoOBE2PFfq', 'registered');

-- PASSWORD REFERENCE FOR TESTING:
-- owner:           owner123
-- staff:           staff123
-- manager:         owner123
-- staff_warehouse: staff123
-- staff_quality:   staff123
-- guest_user:      guest123

INSERT OR IGNORE INTO perfumes (name, description, image_url, top_notes, heart_notes, base_notes, accords, rating) VALUES
('Sauvage (EDP) - Dior', 'By Dior. Price: 145.00', '/assets/perfume_images/sauvage_edp_dior.jpeg', 'Bergamot', 'Sichuan Pepper, Lavender, Star Anise, Nutmeg', 'Ambroxan, Vanilla', 'Aromatic, Citrus, Spicy', 0),
('Bleu de Chanel (Parfum) - Chanel', 'By Chanel. Price: 160.00', '/assets/perfume_images/bleu_de_chanel_parfum.jpeg', 'Lemon Zest, Bergamot, Mint, Artemisia', 'Lavender, Pineapple, Geranium, Green Notes', 'Sandalwood, Cedar, Amberwood, Tonka Bean', 'Woody, Aromatic', 0),
('Acqua di Giò (EDT) - Armani', 'By Armani. Price: 115.00', '/assets/perfume_images/acqua_di_gio_edt_armani.jpeg', 'Lime, Lemon, Bergamot, Jasmine, Orange, Mandarin', 'Sea Notes, Jasmine, Calone, Peach, Freesia', 'White Musk, Cedar, Oakmoss, Patchouli, Amber', 'Aquatic, Fresh, Woody', 0),
('Libre (EDP) - YSL', 'By YSL. Price: 130.00', '/assets/perfume_images/libre_edp_ysl.jpeg', 'Lavender, Mandarin Orange, Black Currant, Petitgrain', 'Lavender, Orange Blossom, Jasmine', 'Madagascar Vanilla, Musk, Cedar, Ambergris', 'Floral, Amber', 0),
('Eros (EDT) - Versace', 'By Versace. Price: 105.00', '/assets/perfume_images/eros_edt_versace.jpeg', 'Mint, Green Apple, Lemon', 'Tonka Bean, Ambroxan, Geranium', 'Madagascar Vanilla, Virginian Cedar, Atlas Cedar, Vetiver', 'Aromatic, Sweet, Woody', 0),
('Luna Rossa Carbon (EDT) - Prada', 'By Prada. Price: 110.00', '/assets/perfume_images/luna_rossa_carbon_edt_prada.jpeg', 'Bergamot, Pepper', 'Lavender, Metallic notes, Coal, Soil tincture, Watery Notes', 'Ambroxan, Patchouli', 'Aromatic, Metallic, Woody', 0),
('Bloom (EDP) - Gucci', 'By Gucci. Price: 125.00', '/assets/perfume_images/bloom_edp_gucci.jpeg', 'Jasmine', 'Tuberose', 'Rangoon Creeper', 'Floral, White Floral', 0),
('Black Orchid (EDP) - Tom Ford', 'By Tom Ford. Price: 150.00', '/assets/perfume_images/black_orchid_edp_tomford.jpeg', 'Truffle, Gardenia, Black Currant, Ylang-Ylang, Bergamot', 'Orchid, Spices, Gardenia, Fruity Notes, Lotus', 'Mexican Chocolate, Patchouli, Vanille, Incense, Amber', 'Oriental, Floral, Gourmand', 0),
('J''adore (EDP) - Dior', 'By Dior. Price: 135.00', '/assets/perfume_images/jadore_edp_dior.jpeg', 'Pear, Melon, Magnolia, Peach, Mandarin Orange, Bergamot', 'Jasmine, Lily-of-the-Valley, Tuberose, Freesia, Rose, Orchid', 'Musk, Vanilla, Blackberry, Cedar', 'Floral, Fruity', 0),
('Flowerbomb (EDP) - Viktor&Rolf', 'By Viktor&Rolf. Price: 145.00', '/assets/perfume_images/flowerbomb_edp_viktorrolf.jpeg', 'Tea, Bergamot, Osmanthus', 'Orchid, Jasmine, Rose, Freesia, Orange Blossom', 'Patchouli, Musk, Vanilla', 'Floral, Oriental', 0),
('Terre d''Hermes (EDT) - Hermes', 'By Hermes. Price: 125.00', '/assets/perfume_images/terre_dhermes_edt.jpeg', 'Orange, Grapefruit', 'Pepper, Pelargonium', 'Vetiver, Cedar, Patchouli, Benzoin', 'Woody, Citrus', 0),
('Alien (EDP) - Mugler', 'By Mugler. Price: 120.00', '/assets/perfume_images/alien_edp_mugler.jpeg', 'Jasmine Sambac', 'Cashmeran', 'White Amber', 'Amber, Floral', 0),
('Her (EDP) - Burberry', 'By Burberry. Price: 125.00', '/assets/perfume_images/her_edp_burberry.jpeg', 'Strawberry, Raspberry, Blackberry, Sour Cherry, Black Currant', 'Jasmine, Violet', 'Musk, Vanilla, Cashmeran, Woody Notes, Amber', 'Fruity, Floral, Gourmand', 0),
('Le Male (EDT) - Jean Paul Gaultier', 'By JP Gaultier. Price: 100.00', '/assets/perfume_images/le_male_edt_jpgaultier.jpeg', 'Lavender, Mint, Cardamom, Bergamot, Artemisia', 'Cinnamon, Orange Blossom, Caraway', 'Vanilla, Tonka Bean, Amber, Sandalwood, Cedar', 'Aromatic, Sweet, Spicy', 0),
('La Vie Est Belle (EDP) - Lancôme', 'By Lancôme. Price: 118.00', '/assets/perfume_images/la_vie_est_belle_edp_lancome.jpeg', 'Black Currant, Pear', 'Iris, Jasmine, Orange Blossom', 'Praline, Vanilla, Patchouli, Tonka Bean', 'Gourmand, Floral', 0),
('Light Blue (EDT) - Dolce & Gabbana', 'By Dolce & Gabbana. Price: 95.00', '/assets/perfume_images/light_blue_edt_dg.jpeg', 'Sicilian Lemon, Apple, Cedar, Bellflower', 'Bamboo, Jasmine, White Rose', 'Cedar, Musk, Amber', 'Fresh, Citrusy', 0),
('Black Opium (EDP) - YSL', 'By YSL. Price: 130.00', '/assets/perfume_images/black_opium_edp_ysl.jpeg', 'Pear, Pink Pepper, Orange Blossom', 'Coffee, Jasmine, Bitter Almond, Licorice', 'Vanilla, Patchouli, Cedar, Cashmere Wood', 'Gourmand, Oriental', 0),
('L''Interdit (EDP) - Givenchy', 'By Givenchy. Price: 120.00', '/assets/perfume_images/linterdit_edp_givenchy.jpeg', 'Pear, Bergamot', 'Tuberose, Orange Blossom, Jasmine Sambac', 'Patchouli, Vanilla, Ambroxan, Vetiver', 'Floral, Woody', 0),
('Born In Roma (EDP) - Valentino', 'By Valentino. Price: 130.00', '/assets/perfume_images/born_in_roma_edp_valentino.jpeg', 'Black Currant, Pink Pepper, Bergamot', 'Jasmine, Jasmine Sambac, Jasmine Tea', 'Bourbon Vanilla, Cashmeran, Guaiac Wood', 'Floral, Woody', 0),
('Man In Black (EDP) - Bvlgari', 'By Bvlgari. Price: 115.00', '/assets/perfume_images/man_in_black_edp_bvlgari.jpeg', 'Spices, Rum, Tobacco', 'Leather, Iris, Tuberose', 'Guaiac Wood, Benzoin, Tonka Bean', 'Oriental, Spicy, Woody', 0),
('L''Homme (EDT) - Prada', 'By Prada. Price: 110.00', '/assets/perfume_images/lhomme_edt_prada.jpeg', 'Neroli, Black Pepper, Cardamom, Carrot Seeds', 'Iris, Violet, Geranium, Mate', 'Patchouli, Cedar, Sandalwood, Amber', 'Aromatic, Woody', 0),
('Bright Crystal (EDT) - Versace', 'By Versace. Price: 95.00', '/assets/perfume_images/bright_crystal_edt_versace.jpeg', 'Yuzu, Pomegranate, Ice', 'Peony, Lotus, Magnolia', 'Musk, Mahogany, Amber', 'Fresh, Floral', 0),
('Coco Mademoiselle (EDP) - Chanel', 'By Chanel. Price: 140.00', '/assets/perfume_images/coco_mademoiselle_edp_chanel.jpeg', 'Orange, Mandarin Orange, Bergamot, Orange Blossom', 'Turkish Rose, Jasmine, Mimosa, Ylang-Ylang', 'Patchouli, White Musk, Vanilla, Vetiver, Tonka Bean', 'Chypre, Floral', 0),
('Daisy (EDT) - Marc Jacobs', 'By Marc Jacobs. Price: 105.00', '/assets/perfume_images/daisy_edt_marcjacobs.jpeg', 'Violet Leaf, Blood Grapefruit, Strawberry', 'Violet, Gardenia, Jasmine', 'Musk, White Woods, Vanilla', 'Fresh, Floral', 0),
('1 Million (EDT) - Paco Rabanne', 'By Paco Rabanne. Price: 110.00', '/assets/perfume_images/1million_edt_pacorabanne.jpeg', 'Blood Mandarin, Grapefruit, Mint', 'Cinnamon, Spicy Notes, Rose', 'Amber, Leather, Woody Notes, Indian Patchouli', 'Sweet, Spicy, Woody', 0),
('Si (EDP) - Armani', 'By Armani. Price: 125.00', '/assets/perfume_images/si_edp_armani.jpeg', 'Cassis', 'May Rose, Freesia', 'Vanilla, Patchouli, Woody Notes, Ambroxan', 'Chypre, Floral', 0),
('Good Girl (EDP) - Carolina Herrera', 'By Carolina Herrera. Price: 130.00', '/assets/perfume_images/good_girl_edp_carolinaherrera.jpeg', 'Almond, Coffee, Bergamot, Lemon', 'Tuberose, Jasmine Sambac, Orris, Orange Blossom', 'Tonka Bean, Cacao, Vanilla, Praline, Sandalwood', 'Oriental, Sweet, Floral', 0),
('For Her (EDT) - Narciso Rodriguez', 'By Narciso Rodriguez. Price: 115.00', '/assets/perfume_images/for_her_edt_narcisorodriguez.jpeg', 'African Orange Flower, Osmanthus, Bergamot', 'Musk, Amber', 'Vetiver, Vanille, Patchouli', 'Musk, Floral', 0),
('Ombré Leather (EDP) - Tom Ford', 'By Tom Ford. Price: 160.00', '/assets/perfume_images/ombre_leather_edp_tomford.jpeg', 'Cardamom', 'Leather, Jasmine Sambac', 'Amber, Moss, Patchouli', 'Leather, Woody', 0),
('Mon Guerlain (EDP) - Guerlain', 'By Guerlain. Price: 125.00', '/assets/perfume_images/mon_guerlain_edp.jpeg', 'Lavender, Bergamot', 'Iris, Jasmine Sambac, Rose', 'Tahitian Vanilla, Coumarin, Sandalwood, Licorice', 'Floral, Sweet', 0),
('Polo Blue (EDT) - Ralph Lauren', 'By Ralph Lauren. Price: 100.00', '/assets/perfume_images/polo_blue_edt_ralphlauren.jpeg', 'Cucumber, Melon, Mandarin Orange', 'Sage, Basil, Geranium', 'Suede, Musk, Woodsy Notes', 'Fresh, Aquatic', 0),
('Boss Bottled (EDT) - Hugo Boss', 'By Hugo Boss. Price: 95.00', '/assets/perfume_images/boss_bottled_edt_hugoboss.jpeg', 'Apple, Plum, Lemon, Bergamot, Oakmoss, Geranium', 'Cinnamon, Mahogany, Carnation', 'Vanilla, Sandalwood, Cedar, Vetiver, Olive Tree', 'Fresh, Woody', 0);

INSERT OR IGNORE INTO about_info (heading, intro, details, features, audience, benefits, stat_1_value, stat_1_label, stat_2_value, stat_2_label, stat_3_value, stat_3_label) VALUES
('Finding the perfect fragrance should not be overwhelming it should be inspiring.', 
 'LabScentique is a comprehensive web-based platform that empowers users to explore and discover perfumes tailored to their unique preferences, while integrating a robust backend system that allows staff to monitor inventory lifecycles and gives business owners a centralized dashboard for managing daily operations.', 
 'Whether you are a fragrance enthusiast seeking detailed scent information or a business owner looking to streamline operations, LabScentique combines personalized recommendations, community engagement, and efficient business operations into one seamless experience. By blending scent expertise with modern technology, we help customers find their signature scents while giving retailers the tools to keep inventory organized and operations on track.', 
 'Personalized Recommendations, Educational Resource & Community Engagement, Inventory & Restocking Management, Business Decision Support', 
 'Ideal for beginners seeking friendly recommendations, occasion-conscious consumers wanting tailored suggestions, fragrance enthusiasts and collectors, and management teams who utilize the platform to monitor daily stock inputs and manage overall business health.', 
 'LabScentique turns perfume exploration into an informed and joyful journey. By combining fragrance passion with business precision, we help shoppers discover new favorites while giving vendors the tools they need to keep operations organized and up to date. We are not just a platform—we are your partner in perfume discovery and management.', 
 '50+', 'Unique formulations', '1000+', 'Satisfied customers', '5★', 'Average rating');

INSERT OR IGNORE INTO inventory (perfume_id, available_quantity, damaged_quantity, expiration_date)
SELECT id, 25, 0, date('now', '+120 days') FROM perfumes;

-- INVENTORY ACCESS CONTROL - Grant staff members permission to manage inventory
-- Staff can manage all inventory (full access)
INSERT OR IGNORE INTO inventory_access (staff_id, inventory_id, access_level, granted_by)
SELECT 
  (SELECT id FROM users WHERE username = 'staff') as staff_id,
  id as inventory_id,
  'manage' as access_level,
  (SELECT id FROM users WHERE username = 'owner') as granted_by
FROM inventory;

-- Warehouse staff: manage inventory
INSERT OR IGNORE INTO inventory_access (staff_id, inventory_id, access_level, granted_by)
SELECT 
  (SELECT id FROM users WHERE username = 'staff_warehouse') as staff_id,
  id as inventory_id,
  'manage' as access_level,
  (SELECT id FROM users WHERE username = 'owner') as granted_by
FROM inventory;

-- Quality staff: view-only access to all inventory (quality check)
INSERT OR IGNORE INTO inventory_access (staff_id, inventory_id, access_level, granted_by)
SELECT 
  (SELECT id FROM users WHERE username = 'staff_quality') as staff_id,
  id as inventory_id,
  'view' as access_level,
  (SELECT id FROM users WHERE username = 'owner') as granted_by
FROM inventory;

-- Product Reviews: Guest/registered users can leave reviews (star rating + comment)
CREATE TABLE IF NOT EXISTS product_reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  perfume_id INTEGER NOT NULL,
  user_id INTEGER,
  -- user_id can be NULL for guest reviews (identified by guest_email + guest_name)
  guest_name TEXT,
  guest_email TEXT,
  -- At least one of user_id or (guest_name + guest_email) must be set
  rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
  -- rating: 1-5 stars
  comment TEXT,
  is_visible_to_guests BOOLEAN DEFAULT 0,
  -- is_visible_to_guests: 1 = visible to non-login users and staff/owner, 0 = visible only to staff/owner
  created_at DATETIME NOT NULL DEFAULT (datetime('now')),
  updated_at DATETIME NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Review Replies: Staff/owner can reply to reviews
CREATE TABLE IF NOT EXISTS review_replies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  review_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  -- user_id: staff or owner who is replying
  reply_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT (datetime('now')),
  updated_at DATETIME NOT NULL DEFAULT (datetime('now')),
  UNIQUE(review_id, user_id),
  -- Only one reply per staff/owner per review
  FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

