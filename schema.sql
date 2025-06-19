-- CMS Admin Panel Database Schema

-- Admin authentication
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Business data (single record)
CREATE TABLE business_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    header_logo VARCHAR(255),
    footer_logo VARCHAR(255),
    business_name VARCHAR(255) NOT NULL,
    address TEXT,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    instagram_link VARCHAR(255),
    facebook_link VARCHAR(255),
    twitter_link VARCHAR(255),
    web_credits TEXT,
    copyright_content TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hero sliders (multiple records)
CREATE TABLE hero_sliders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    image VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Testimonials (multiple records)
CREATE TABLE testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    photo VARCHAR(255),
    name VARCHAR(100) NOT NULL,
    designation VARCHAR(100),
    organization VARCHAR(100),
    content TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- About content (single record)
CREATE TABLE about_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    about_content TEXT NOT NULL,
    mission TEXT,
    vision TEXT,
    years_experience INT,
    projects_completed INT,
    happy_clients INT,
    team_members INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Our journey (multiple records)
CREATE TABLE our_journey (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Our values (multiple records)
CREATE TABLE our_values (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Industry categories (multiple records)
CREATE TABLE industry_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    content TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Category key features (multiple per category)
CREATE TABLE category_key_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    feature_text TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES industry_categories(id) ON DELETE CASCADE
);

-- Projects (multiple records)
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    industry_category_id INT NOT NULL,
    location VARCHAR(255),
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    project_overview TEXT,
    video_url VARCHAR(255),
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (industry_category_id) REFERENCES industry_categories(id)
);

-- Project gallery images (multiple per project)
CREATE TABLE project_gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Services (multiple records)
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    our_expertise TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service offerings (multiple per service)
CREATE TABLE service_offerings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    offering_text TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Service benefits (multiple per service)
CREATE TABLE service_benefits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    benefit_text TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Contact settings
CREATE TABLE contact_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    office_hours TEXT,
    google_map_embed TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Form enquiries
CREATE TABLE form_enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'responded') DEFAULT 'new'
);

-- Latest Updates
CREATE TABLE IF NOT EXISTS latest_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255),
    is_visible BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Latest News
CREATE TABLE IF NOT EXISTS latest_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255),
    is_visible BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert initial business data record
INSERT INTO business_data (business_name, address, email, phone, copyright_content)
VALUES ('Swifcon', '123 Business Street, City, Country', 'contact@swifcon.com', '+1234567890', '© 2025 Swifcon. All rights reserved.');

-- Insert initial contact settings
INSERT INTO contact_settings (office_hours) VALUES ('Monday - Friday: 9:00 AM - 5:00 PM');

-- Create indexes for better performance
CREATE INDEX idx_hero_sliders_status ON hero_sliders(status);
CREATE INDEX idx_testimonials_status ON testimonials(status);
CREATE INDEX idx_industry_categories_status ON industry_categories(status);
CREATE INDEX idx_projects_category_status ON projects(industry_category_id, status);
CREATE INDEX idx_services_status ON services(status);
CREATE INDEX idx_form_enquiries_status ON form_enquiries(status);
