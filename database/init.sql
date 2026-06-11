CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT, updated_at TEXT);
CREATE TABLE IF NOT EXISTS services (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT, icon TEXT, sort_order INTEGER DEFAULT 0);
CREATE TABLE IF NOT EXISTS reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, author TEXT NOT NULL, text TEXT NOT NULL, rating INTEGER DEFAULT 5, is_published INTEGER DEFAULT 0);
CREATE TABLE IF NOT EXISTS leads (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT NOT NULL, service_id INTEGER, message TEXT, created_at TEXT, is_processed INTEGER DEFAULT 0);
CREATE TABLE IF NOT EXISTS admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL);
INSERT OR IGNORE INTO settings (key, value) VALUES ('site_title', 'GuruFix'), ('meta_description', 'Качественный ремонт и обслуживание'), ('primary_color', '#ff6600'), ('font_family', 'Arial, sans-serif'), ('telegram_bot_token', ''), ('telegram_chat_id', ''), ('smtp_host', ''), ('smtp_user', ''), ('smtp_pass', ''), ('admin_email', 'admin@example.com');
INSERT OR IGNORE INTO services (id, title, description, sort_order) VALUES (1, 'Ремонт компьютеров', 'Быстрый ремонт любой сложности', 1);
INSERT OR IGNORE INTO reviews (id, author, text, rating, is_published) VALUES (1, 'Анна', 'Отличный сервис!', 5, 1);
INSERT OR IGNORE INTO admin_users (username, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
