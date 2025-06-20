-- ================================================================
-- HIREWAY - COMPLETE DATABASE SETUP
-- Job Portal Application Database (MySQL/MariaDB Compatible)
-- Created: June 20, 2025
-- Version: 1.0 Final
-- ================================================================

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS hireway;
CREATE DATABASE hireway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hireway;

-- ================================================================
-- SECTION 1: CORE TABLES CREATION
-- ================================================================

-- 1.1 USERS TABLE - Menyimpan data pengguna (pencari kerja, employer, admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('jobseeker', 'employer', 'admin') DEFAULT 'jobseeker',
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(100),
    password_reset_token VARCHAR(100),
    password_reset_expires DATETIME,
    last_login DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    company_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_email_verified (email_verified),
    INDEX idx_is_active (is_active),
    INDEX idx_company_id (company_id)
);

-- 1.2 USER PROFILES TABLE - Detail profil pencari kerja
CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(150),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    bio TEXT,
    experience_years INT DEFAULT 0,
    education_level ENUM('SMA', 'D3', 'S1', 'S2', 'S3'),
    skills TEXT, -- JSON format: ["PHP", "JavaScript", "MySQL"]
    preferred_job_type VARCHAR(50),
    preferred_location VARCHAR(100),
    salary_expectation_min INT,
    salary_expectation_max INT,
    cv_file VARCHAR(255),
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    availability ENUM('immediately', 'one_week', 'two_weeks', 'one_month') DEFAULT 'immediately',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_preferred_location (preferred_location),
    INDEX idx_experience_years (experience_years),
    INDEX idx_salary_range (salary_expectation_min, salary_expectation_max)
);

-- 1.3 COMPANIES TABLE - Data perusahaan
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE,
    description TEXT,
    industry VARCHAR(100),
    company_size ENUM('1-10', '11-50', '51-200', '201-500', '500+'),
    founded_year YEAR,
    website VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    logo VARCHAR(255),
    cover_image VARCHAR(255),
    social_media JSON,
    is_verified BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_slug (slug),
    INDEX idx_industry (industry),
    INDEX idx_city (city),
    INDEX idx_is_verified (is_verified),
    INDEX idx_is_premium (is_premium)
);

-- 1.4 CATEGORIES TABLE - Kategori pekerjaan
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
);

-- 1.5 JOBS TABLE - Lowongan pekerjaan
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    company VARCHAR(150) NOT NULL,
    company_id INT,
    category_id INT,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    benefits TEXT,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'freelance') DEFAULT 'full-time',
    work_location ENUM('onsite', 'remote', 'hybrid') DEFAULT 'onsite',
    location VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    salary VARCHAR(100),
    salary_min INT,
    salary_max INT,
    experience_required ENUM('fresh-graduate', '1-2-years', '3-5-years', '5+-years'),
    education_required ENUM('SMA', 'D3', 'S1', 'S2', 'S3'),
    skills_required JSON,
    application_deadline DATE,
    external_url VARCHAR(500),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    status ENUM('draft', 'published', 'paused', 'closed', 'expired') DEFAULT 'published',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_title (title),
    INDEX idx_slug (slug),
    INDEX idx_company (company),
    INDEX idx_company_id (company_id),
    INDEX idx_category_id (category_id),
    INDEX idx_job_type (job_type),
    INDEX idx_work_location (work_location),
    INDEX idx_location (location, city, province),
    INDEX idx_salary_range (salary_min, salary_max),
    INDEX idx_status_active (status, is_active),
    INDEX idx_featured_urgent (is_featured, is_urgent),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_search (title, company, description, requirements)
);

-- ================================================================
-- SECTION 2: INTERACTION TABLES
-- ================================================================

-- 2.1 APPLICATIONS TABLE - Lamaran pekerjaan
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    cover_letter TEXT,
    cv_file VARCHAR(255),
    status ENUM('pending', 'reviewed', 'interview', 'accepted', 'rejected') DEFAULT 'pending',
    notes TEXT,
    interview_date DATETIME,
    interview_location VARCHAR(255),
    interview_notes TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id),
    
    INDEX idx_job_id (job_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_applied_at (applied_at)
);

-- 2.2 BOOKMARKS TABLE - Pekerjaan yang di-bookmark
CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, job_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_job_id (job_id)
);

-- 2.3 JOB VIEWS TABLE - Tracking views pekerjaan
CREATE TABLE job_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_job_id (job_id),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at),
    INDEX idx_ip_address (ip_address)
);

-- 2.4 REVIEWS TABLE - Review perusahaan
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    review TEXT,
    pros TEXT,
    cons TEXT,
    work_life_balance_rating INT CHECK (work_life_balance_rating >= 1 AND work_life_balance_rating <= 5),
    salary_rating INT CHECK (salary_rating >= 1 AND salary_rating <= 5),
    career_growth_rating INT CHECK (career_growth_rating >= 1 AND career_growth_rating <= 5),
    management_rating INT CHECK (management_rating >= 1 AND management_rating <= 5),
    is_current_employee BOOLEAN DEFAULT FALSE,
    job_position VARCHAR(100),
    employment_duration VARCHAR(50),
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_company_id (company_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_is_approved (is_approved),
    INDEX idx_created_at (created_at)
);

-- ================================================================
-- SECTION 3: SYSTEM TABLES
-- ================================================================

-- 3.1 NOTIFICATIONS TABLE - Notifikasi sistem
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('application', 'job_alert', 'system', 'promotion') DEFAULT 'system',
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- 3.2 JOB ALERTS TABLE - Alert pekerjaan
CREATE TABLE job_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    keywords VARCHAR(255),
    location VARCHAR(100),
    category_id INT,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'freelance'),
    salary_min INT,
    frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    is_active BOOLEAN DEFAULT TRUE,
    last_sent DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_frequency (frequency)
);

-- 3.3 ACTIVITY LOGS TABLE - Log aktivitas pengguna
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- ================================================================
-- SECTION 4: INITIAL DATA
-- ================================================================

-- 4.1 Insert Default Admin User
INSERT INTO users (name, email, password, role, email_verified, is_active) VALUES
('Administrator', 'admin@hireway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1);

-- 4.2 Insert Categories
INSERT INTO categories (name, slug, description, icon, color, sort_order) VALUES
('Technology', 'technology', 'Software development, IT, and tech-related jobs', 'fas fa-laptop-code', '#3B82F6', 1),
('Marketing', 'marketing', 'Digital marketing, advertising, and promotion jobs', 'fas fa-bullhorn', '#EF4444', 2),
('Design', 'design', 'UI/UX, graphic design, and creative jobs', 'fas fa-paint-brush', '#8B5CF6', 3),
('Sales', 'sales', 'Sales representative, account manager jobs', 'fas fa-chart-line', '#10B981', 4),
('Finance', 'finance', 'Accounting, financial analyst, banking jobs', 'fas fa-calculator', '#F59E0B', 5),
('Human Resources', 'human-resources', 'HR, recruitment, and people management', 'fas fa-users', '#EC4899', 6),
('Customer Service', 'customer-service', 'Customer support and service jobs', 'fas fa-headset', '#6366F1', 7),
('Operations', 'operations', 'Operations, logistics, and supply chain jobs', 'fas fa-cogs', '#059669', 8);

-- 4.3 Insert Sample Companies
INSERT INTO companies (name, slug, description, industry, company_size, founded_year, website, email, city, province, is_verified) VALUES
('TechCorp Indonesia', 'techcorp-indonesia', 'Leading technology company specializing in software development and digital solutions', 'Technology', '201-500', 2010, 'https://techcorp.id', 'hr@techcorp.id', 'Jakarta', 'DKI Jakarta', TRUE),
('Digital Marketing Pro', 'digital-marketing-pro', 'Full-service digital marketing agency helping businesses grow online', 'Marketing', '51-200', 2015, 'https://digitalmarketingpro.com', 'careers@digitalmarketingpro.com', 'Bandung', 'Jawa Barat', TRUE),
('Creative Studio Indonesia', 'creative-studio-indonesia', 'Innovative design studio creating beautiful digital experiences', 'Creative', '11-50', 2018, 'https://creativestudio.co.id', 'jobs@creativestudio.co.id', 'Yogyakarta', 'DI Yogyakarta', FALSE),
('FinanceHub', 'financehub', 'Financial services and consulting company', 'Finance', '101-200', 2012, 'https://financehub.co.id', 'hr@financehub.co.id', 'Surabaya', 'Jawa Timur', TRUE);

-- 4.4 Insert Sample Jobs
INSERT INTO jobs (title, slug, company, company_id, category_id, description, requirements, responsibilities, benefits, job_type, work_location, location, city, province, salary_min, salary_max, experience_required, education_required, skills_required, application_deadline, posted_by) VALUES
('Senior PHP Developer', 'senior-php-developer-techcorp', 'TechCorp Indonesia', 1, 1, 
'We are looking for a skilled Senior PHP Developer to join our dynamic development team. You will be responsible for developing and maintaining web applications using PHP and related technologies.',
'• Minimum 5 years of PHP development experience\n• Strong knowledge of Laravel framework\n• Experience with MySQL database\n• Familiarity with Git version control\n• Good understanding of RESTful APIs',
'• Develop and maintain web applications\n• Write clean, maintainable code\n• Collaborate with cross-functional teams\n• Code review and mentoring junior developers\n• Troubleshoot and debug applications',
'• Competitive salary\n• Health insurance\n• Flexible working hours\n• Professional development opportunities\n• Annual bonus',
'full-time', 'hybrid', 'Jakarta Selatan', 'Jakarta', 'DKI Jakarta', 12000000, 18000000, '5+-years', 'S1', 
'["PHP", "Laravel", "MySQL", "Git", "REST API"]', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),

('UI/UX Designer', 'ui-ux-designer-creative-studio', 'Creative Studio Indonesia', 3, 3,
'Join our creative team as a UI/UX Designer! We are seeking a talented individual who can create intuitive and engaging user experiences for web and mobile applications.',
'• Minimum 2 years of UI/UX design experience\n• Proficiency in Figma and Adobe Creative Suite\n• Strong portfolio showcasing web and mobile designs\n• Understanding of user-centered design principles\n• Knowledge of prototyping tools',
'• Create wireframes, prototypes, and high-fidelity designs\n• Conduct user research and usability testing\n• Collaborate with developers and product managers\n• Maintain design systems and style guides\n• Present design concepts to stakeholders',
'• Creative work environment\n• Health and dental insurance\n• Flexible schedule\n• Design tools and equipment provided\n• Team building activities',
'full-time', 'onsite', 'Sleman', 'Yogyakarta', 'DI Yogyakarta', 7000000, 12000000, '1-2-years', 'S1',
'["Figma", "Adobe XD", "Photoshop", "UI Design", "UX Research"]', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 1),

('Digital Marketing Specialist', 'digital-marketing-specialist-dmp', 'Digital Marketing Pro', 2, 2,
'We are seeking a creative and analytical Digital Marketing Specialist to develop, implement, and manage marketing campaigns that promote our company and its products/services.',
'• Bachelor degree in Marketing or related field\n• 2+ years of digital marketing experience\n• Experience with Google Ads and Facebook Ads\n• Knowledge of SEO and SEM\n• Strong analytical skills',
'• Plan and execute digital marketing campaigns\n• Manage social media accounts\n• Analyze campaign performance and ROI\n• Create content for various digital platforms\n• Stay updated with digital marketing trends',
'• Competitive salary package\n• Performance bonuses\n• Health insurance\n• Training and certification opportunities\n• Modern office environment',
'full-time', 'hybrid', 'Bandung', 'Bandung', 'Jawa Barat', 6000000, 10000000, '1-2-years', 'S1',
'["Google Ads", "Facebook Ads", "SEO", "Content Marketing", "Analytics"]', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 1),

('Financial Analyst', 'financial-analyst-financehub', 'FinanceHub', 4, 5,
'We are looking for a detail-oriented Financial Analyst to join our finance team. The ideal candidate will analyze financial data and provide insights to support business decisions.',
'• Bachelor degree in Finance, Accounting, or Economics\n• 2-3 years of financial analysis experience\n• Proficiency in Excel and financial modeling\n• Knowledge of accounting principles\n• Strong analytical and problem-solving skills',
'• Analyze financial data and create reports\n• Develop financial models and forecasts\n• Monitor budget performance\n• Support investment decisions\n• Prepare presentations for management',
'• Attractive salary package\n• Professional certification support\n• Health and life insurance\n• Career advancement opportunities\n• Annual performance bonus',
'full-time', 'onsite', 'Surabaya', 'Surabaya', 'Jawa Timur', 8000000, 13000000, '1-2-years', 'S1',
'["Excel", "Financial Modeling", "Data Analysis", "Accounting", "PowerPoint"]', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 1);

-- ================================================================
-- SECTION 5: VIEWS FOR EASY QUERYING
-- ================================================================

-- 5.1 Enhanced job listings view
CREATE VIEW job_listings_view AS
SELECT 
    j.id,
    j.title,
    j.slug,
    j.company,
    c.logo as company_logo,
    c.is_verified as company_verified,
    cat.name as category_name,
    cat.color as category_color,
    j.job_type,
    j.work_location,
    j.location,
    j.city,
    j.province,
    CASE 
        WHEN j.salary_min IS NOT NULL AND j.salary_max IS NOT NULL THEN 
            CONCAT('Rp ', FORMAT(j.salary_min, 0), ' - Rp ', FORMAT(j.salary_max, 0))
        WHEN j.salary_min IS NOT NULL THEN 
            CONCAT('Rp ', FORMAT(j.salary_min, 0))
        ELSE 'Negotiable'
    END AS formatted_salary,
    j.experience_required,
    j.education_required,
    j.is_featured,
    j.is_urgent,
    j.views_count,
    j.applications_count,
    j.application_deadline,
    CASE 
        WHEN DATEDIFF(NOW(), j.created_at) = 0 THEN 'Hari ini'
        WHEN DATEDIFF(NOW(), j.created_at) = 1 THEN '1 hari yang lalu'
        WHEN DATEDIFF(NOW(), j.created_at) < 7 THEN CONCAT(DATEDIFF(NOW(), j.created_at), ' hari yang lalu')
        WHEN DATEDIFF(NOW(), j.created_at) < 30 THEN CONCAT(FLOOR(DATEDIFF(NOW(), j.created_at)/7), ' minggu yang lalu')
        ELSE CONCAT(FLOOR(DATEDIFF(NOW(), j.created_at)/30), ' bulan yang lalu')
    END AS posted_time,
    j.created_at,
    j.status,
    j.is_active
FROM jobs j
LEFT JOIN companies c ON j.company_id = c.id
LEFT JOIN categories cat ON j.category_id = cat.id
WHERE j.is_active = TRUE AND j.status = 'published';

-- 5.2 User statistics view
CREATE VIEW user_stats_view AS
SELECT 
    role,
    COUNT(*) as total_users,
    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
FROM users 
GROUP BY role;

-- ================================================================
-- SECTION 6: INDEXES FOR PERFORMANCE
-- ================================================================

-- Additional composite indexes for better query performance
CREATE INDEX idx_jobs_location_type ON jobs(city, job_type, is_active);
CREATE INDEX idx_jobs_category_location ON jobs(category_id, city, is_active);
CREATE INDEX idx_applications_status_date ON applications(status, applied_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at);

-- ================================================================
-- FINAL SETUP COMPLETE
-- Default Admin Credentials:
-- Email: admin@hireway.com  
-- Password: password (default Laravel hash for 'password')
-- 
-- SECURITY NOTE: Change admin password after first login!
-- ================================================================
