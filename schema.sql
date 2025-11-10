CREATE TABLE IF NOT EXISTS incidents (
    id CHAR(36) NOT NULL PRIMARY KEY,
    incident_title VARCHAR(255) NOT NULL,
    area VARCHAR(255),
    plant VARCHAR(255),
    incident_date DATE NOT NULL,
    incident_time TIME,
    shift VARCHAR(50),
    incident_type VARCHAR(100),
    body_part_affected VARCHAR(100),
    description TEXT,
    comments TEXT,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    immediate_actions_taken TEXT,
    chairman VARCHAR(255),
    investigator VARCHAR(255),
    safety_officer VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidents_status (status),
    INDEX idx_incidents_incident_date (incident_date)
);

CREATE TABLE IF NOT EXISTS rca_answers (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    incident_id CHAR(36) NOT NULL,
    position INT NOT NULL DEFAULT 1,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    INDEX idx_rca_answers_incident_id (incident_id)
);

CREATE TABLE IF NOT EXISTS safety_walks (
    id CHAR(36) NOT NULL PRIMARY KEY,
    walk_date DATE NOT NULL,
    walk_time TIME,
    site VARCHAR(255) NOT NULL,
    area VARCHAR(255),
    mode VARCHAR(100),
    contact VARCHAR(255),
    is_virtual TINYINT(1) NOT NULL DEFAULT 0,
    comments TEXT,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    reported_by VARCHAR(255),
    reported_by_role VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_safety_walks_status (status),
    INDEX idx_safety_walks_walk_date (walk_date)
);

CREATE TABLE IF NOT EXISTS safety_walk_findings (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    safety_walk_id CHAR(36) NOT NULL,
    finding_type ENUM('good_practice', 'point_of_improvement') NOT NULL,
    description TEXT,
    signature_url LONGTEXT,
    photos_json JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (safety_walk_id) REFERENCES safety_walks(id) ON DELETE CASCADE,
    INDEX idx_safety_walk_findings_walk_id (safety_walk_id)
);

CREATE TABLE IF NOT EXISTS safety_walk_responses (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    safety_walk_id CHAR(36) NOT NULL,
    category VARCHAR(255),
    position INT NOT NULL DEFAULT 1,
    question TEXT NOT NULL,
    answer TEXT,
    score DECIMAL(5,2),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (safety_walk_id) REFERENCES safety_walks(id) ON DELETE CASCADE,
    INDEX idx_safety_walk_responses_walk_id (safety_walk_id)
);

CREATE TABLE IF NOT EXISTS audits (
    id CHAR(36) NOT NULL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    area VARCHAR(255),
    template VARCHAR(255),
    site VARCHAR(255),
    contact VARCHAR(255),
    is_virtual TINYINT(1) NOT NULL DEFAULT 0,
    comments TEXT,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    reported_by VARCHAR(255),
    reported_by_role VARCHAR(255),
    audit_date DATE NOT NULL,
    audit_time TIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_audits_status (status),
    INDEX idx_audits_audit_date (audit_date)
);

CREATE TABLE IF NOT EXISTS audit_responses (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    audit_id CHAR(36) NOT NULL,
    position INT NOT NULL DEFAULT 1,
    question TEXT NOT NULL,
    answer ENUM('yes', 'no', 'na') NOT NULL DEFAULT 'na',
    observation TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    INDEX idx_audit_responses_audit_id (audit_id)
);

CREATE TABLE IF NOT EXISTS audit_observations (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    audit_id CHAR(36) NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    INDEX idx_audit_observations_audit_id (audit_id)
);

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    profile_image_url LONGTEXT,
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    country VARCHAR(100),
    state VARCHAR(100),
    district VARCHAR(100),
    zipcode VARCHAR(20),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    added_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_roles (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    role_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_roles_user_id (user_id)
);

CREATE TABLE IF NOT EXISTS user_branches (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    branch_location VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_branches_user_id (user_id)
);


