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


