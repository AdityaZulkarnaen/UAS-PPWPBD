-- Additional Tables for HireWay Application
-- Created: June 8, 2025

-- ================================================
-- Table: categories (Kategori Pekerjaan)
-- ================================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-briefcase',
    slug VARCHAR(100) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, description, icon, slug) VALUES
('Technology', 'Pekerjaan di bidang teknologi informasi', 'fas fa-laptop-code', 'technology'),
('Marketing', 'Pekerjaan di bidang pemasaran dan promosi', 'fas fa-bullhorn', 'marketing'),
('Finance', 'Pekerjaan di bidang keuangan dan akuntansi', 'fas fa-chart-line', 'finance'),
('Healthcare', 'Pekerjaan di bidang kesehatan dan medis', 'fas fa-heartbeat', 'healthcare'),
('Education', 'Pekerjaan di bidang pendidikan dan pengajaran', 'fas fa-graduation-cap', 'education'),
('Design', 'Pekerjaan di bidang desain grafis dan kreatif', 'fas fa-palette', 'design'),
('Sales', 'Pekerjaan di bidang penjualan dan retail', 'fas fa-handshake', 'sales'),
('Engineering', 'Pekerjaan di bidang teknik dan manufaktur', 'fas fa-cogs', 'engineering'),
('Human Resources', 'Pekerjaan di bidang sumber daya manusia', 'fas fa-users', 'human-resources'),
('Customer Service', 'Pekerjaan di bidang layanan pelanggan', 'fas fa-headset', 'customer-service');

-- ================================================
-- Table: user_profiles (Profil Pengguna Lengkap)
-- ================================================
CREATE TABLE user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    bio TEXT,
    avatar_path VARCHAR(255),
    resume_path VARCHAR(255),
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    website_url VARCHAR(255),
    experience_years INT DEFAULT 0,
    education_level ENUM('sma', 'diploma', 's1', 's2', 's3', 'other') DEFAULT NULL,
    salary_expectation_min INT DEFAULT NULL,
    salary_expectation_max INT DEFAULT NULL,
    preferred_location VARCHAR(255),
    preferred_job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'freelance') DEFAULT NULL,
    job_status ENUM('open_to_work', 'employed', 'not_looking') DEFAULT 'open_to_work',
    skills TEXT, -- JSON format: ["PHP", "JavaScript", "MySQL"]
    languages TEXT, -- JSON format: [{"name": "English", "level": "fluent"}, {"name": "Indonesian", "level": "native"}]
    is_profile_complete BOOLEAN DEFAULT FALSE,
    profile_visibility ENUM('public', 'private', 'recruiter_only') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ================================================
-- Table: applications (Lamaran Kerja)
-- ================================================
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    cover_letter TEXT,
    resume_path VARCHAR(255),
    portfolio_url VARCHAR(255),
    expected_salary VARCHAR(100),
    available_start_date DATE,
    status ENUM('pending', 'reviewed', 'shortlisted', 'interview', 'accepted', 'rejected') DEFAULT 'pending',
    notes TEXT, -- Catatan dari recruiter
    viewed_by_employer BOOLEAN DEFAULT FALSE,
    viewed_at TIMESTAMP NULL,
    employer_feedback TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id) -- Prevent duplicate applications
);

-- ================================================
-- Additional helpful tables for better functionality
-- ================================================

-- Table: job_categories (Many-to-many relationship between jobs and categories)
CREATE TABLE job_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_job_category (job_id, category_id)
);

-- Table: bookmarks (Simpan Lowongan Favorit)
CREATE TABLE bookmarks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, job_id)
);

-- Table: notifications (Sistem Notifikasi)
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('application', 'job_alert', 'system', 'reminder', 'interview') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT NULL, -- ID yang terkait (job_id, application_id, etc)
    related_type ENUM('job', 'application', 'user', 'system') DEFAULT 'system',
    action_url VARCHAR(500), -- URL untuk action button
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: job_views (Tracking Views Lowongan)
CREATE TABLE job_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ================================================
-- Indexes for better performance
-- ================================================
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_job_id ON applications(job_id);
CREATE INDEX idx_applications_user_id ON applications(user_id);
CREATE INDEX idx_applications_applied_at ON applications(applied_at);

CREATE INDEX idx_user_profiles_job_status ON user_profiles(job_status);
CREATE INDEX idx_user_profiles_experience ON user_profiles(experience_years);
CREATE INDEX idx_user_profiles_location ON user_profiles(preferred_location);

CREATE INDEX idx_categories_active ON categories(is_active);
CREATE INDEX idx_categories_slug ON categories(slug);

CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_type ON notifications(type);

CREATE INDEX idx_job_views_job_date ON job_views(job_id, viewed_at);
CREATE INDEX idx_bookmarks_user ON bookmarks(user_id);

-- ================================================
-- Sample triggers for automatic updates
-- ================================================

-- Trigger to update profile completion status
DELIMITER //
CREATE TRIGGER update_profile_completion 
BEFORE UPDATE ON user_profiles
FOR EACH ROW
BEGIN
    -- Check if profile is complete (basic required fields)
    IF (NEW.first_name IS NOT NULL AND NEW.first_name != '' AND
        NEW.last_name IS NOT NULL AND NEW.last_name != '' AND
        NEW.phone IS NOT NULL AND NEW.phone != '' AND
        NEW.bio IS NOT NULL AND NEW.bio != '' AND
        NEW.experience_years IS NOT NULL) THEN
        SET NEW.is_profile_complete = TRUE;
    ELSE
        SET NEW.is_profile_complete = FALSE;
    END IF;
END//
DELIMITER ;

-- Trigger to create notification when application status changes
DELIMITER //
CREATE TRIGGER application_status_notification
AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (
            NEW.user_id,
            CONCAT('Status Lamaran Diperbarui'),
            CONCAT('Status lamaran Anda untuk posisi telah diperbarui menjadi: ', NEW.status),
            'application',
            NEW.id,
            'application'
        );
    END IF;
END//
DELIMITER ;

-- ================================================
-- Views for easier data retrieval
-- ================================================

-- View for complete application details
CREATE VIEW application_details AS
SELECT 
    a.*,
    j.title as job_title,
    j.company as job_company,
    j.location as job_location,
    j.salary as job_salary,
    j.job_type,
    u.username,
    u.email as user_email,
    up.first_name,
    up.last_name,
    up.phone,
    CONCAT(up.first_name, ' ', up.last_name) as full_name
FROM applications a
LEFT JOIN jobs j ON a.job_id = j.id
LEFT JOIN users u ON a.user_id = u.id
LEFT JOIN user_profiles up ON u.id = up.user_id;

-- View for job statistics
CREATE VIEW job_statistics AS
SELECT 
    j.id,
    j.title,
    j.company,
    COUNT(DISTINCT a.id) as total_applications,
    COUNT(DISTINCT jv.id) as total_views,
    COUNT(DISTINCT b.id) as total_bookmarks,
    COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending_applications,
    COUNT(DISTINCT CASE WHEN a.status = 'accepted' THEN a.id END) as accepted_applications
FROM jobs j
LEFT JOIN applications a ON j.id = a.job_id
LEFT JOIN job_views jv ON j.id = jv.job_id
LEFT JOIN bookmarks b ON j.id = b.job_id
GROUP BY j.id, j.title, j.company;
