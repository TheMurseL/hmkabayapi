CREATE DATABASE IF NOT EXISTS insaat_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE insaat_portfolio;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  category ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
  location VARCHAR(160) NOT NULL,
  year VARCHAR(20) NOT NULL,
  area VARCHAR(80) DEFAULT NULL,
  summary TEXT NOT NULL,
  description TEXT NOT NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  building_type VARCHAR(180) DEFAULT NULL,
  work_scope TEXT DEFAULT NULL,
  quantities TEXT DEFAULT NULL,
  work_process TEXT DEFAULT NULL,
  challenges TEXT DEFAULT NULL,
  delivery_result TEXT DEFAULT NULL,
  image VARCHAR(500) DEFAULT NULL,
  seo_title VARCHAR(180) DEFAULT NULL,
  seo_description VARCHAR(320) DEFAULT NULL,
  featured TINYINT(1) DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  intro TEXT NOT NULL,
  sections LONGTEXT NOT NULL,
  process LONGTEXT NOT NULL,
  questions LONGTEXT NOT NULL,
  seo_title VARCHAR(180) DEFAULT NULL,
  seo_description VARCHAR(320) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_services (
  project_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, service_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  excerpt TEXT NOT NULL,
  content LONGTEXT NOT NULL,
  image VARCHAR(500) DEFAULT NULL,
  seo_title VARCHAR(180) DEFAULT NULL,
  seo_description VARCHAR(320) DEFAULT NULL,
  author_name VARCHAR(180) DEFAULT NULL,
  reviewer_name VARCHAR(180) DEFAULT NULL,
  reviewed_at DATE DEFAULT NULL,
  sources TEXT DEFAULT NULL,
  search_intent VARCHAR(180) DEFAULT NULL,
  published TINYINT(1) DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  message TEXT NOT NULL,
  request_token CHAR(64) NULL UNIQUE,
  district VARCHAR(100) NULL,
  building_type VARCHAR(100) NULL,
  area DECIMAL(12,2) NULL,
  stage VARCHAR(100) NULL,
  source VARCHAR(500) NULL,
  lead_status VARCHAR(30) NOT NULL DEFAULT 'new',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL
);
CREATE TABLE IF NOT EXISTS post_services (
  post_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, service_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS post_projects (
  post_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, project_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_gallery (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  image VARCHAR(500) NOT NULL,
  caption VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_project_gallery_project (project_id),
  CONSTRAINT fk_project_gallery_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);


INSERT INTO admins (name, email, password) VALUES ('Site Yöneticisi', 'admin@example.com', '$2y$10$M8OUuJCbSFumGSbfnZnGVOGOp70Yrh4e6xKurKSh4CxSZgeqSqpTa') ON DUPLICATE KEY UPDATE email=email;
INSERT INTO projects (title, slug, category, location, year, area, summary, description, image, featured, is_demo) VALUES
('Çınar Evleri', 'cinar-evleri', 'ongoing', 'Kocaeli · Başiskele', '2025—2026', '2.400 m²', 'Villa yapısında temel, kolon ve döşeme kalıpları.', 'Villa projesi için ahşap kalıp işçiliği kapsamı.', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=85', 1, 1),
('Marmara Konutları', 'marmara-konutlari', 'completed', 'İstanbul · Tuzla', '2024', '6.800 m²', 'Konut projesinde ahşap kalıp işçiliği.', 'Konut yapısının temel ve kat kalıplarına yönelik işçilik kapsamı.', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85', 1, 1),
('Köyceğiz Villaları', 'koycegiz-villalari', 'completed', 'Muğla · Köyceğiz', '2023', '1.100 m²', 'Villa yapıları için tahta ile kalıp işi.', 'Villa yapılarında temel, kolon, kiriş ve döşeme kalıplarının işçilik kapsamı.', 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1200&q=85', 0, 1);
INSERT INTO posts (title, slug, excerpt, content, image, published) VALUES
('Kalıp işinde çalışma sırası', 'kaba-insaatta-dogru-is-programi', 'Kalıp işi başlamadan önce proje, metraj ve demir işlerinin sırası netleştirilir.', 'Kalıp yapılacak bölümleri projeden inceleyerek işe başlıyoruz. Kalıp hazırlığı, demir donatı ve beton uygulamalarının sırasını birlikte planlıyoruz. Tuğla veya çatı işi isteniyorsa bunları da iş programına ekliyoruz.', 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1200&q=85', 0),
('Ahşap kalıp işçiliğinde nelere bakıyoruz?', 'betonarme-uygulamalarda-dikkat-ettigimiz-5-konu', 'Kalıp ölçüleri, tahta hazırlığı ve uygulamaların çalışma sırası.', 'Ahşap kalıbı çakmadan önce uygulama projesindeki ölçüleri inceliyoruz. Tahta ve kereste hazırlığını yapılacak işe göre planlıyoruz. Kalıp hazırlığını demir donatı ve beton uygulama programına göre planlıyoruz.', 'https://images.unsplash.com/photo-1590661642637-4e3e5f9b8b1c?auto=format&fit=crop&w=1200&q=85', 0);
