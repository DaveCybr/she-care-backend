

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    reset_token VARCHAR(100),
    reset_token_expire DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB;

-- Diseases table with multi-language support
CREATE TABLE diseases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_id VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    description_id TEXT,
    description_en TEXT,
    severity ENUM('low', 'moderate', 'high') DEFAULT 'moderate',
    recommendations_id TEXT,
    recommendations_en TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Questions table with multi-language support
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_text_id TEXT NOT NULL,
    question_text_en TEXT NOT NULL,
    question_type ENUM('scale', 'yesno', 'multiple') DEFAULT 'scale',
    min_value INT DEFAULT 1,
    max_value INT DEFAULT 5,
    order_number INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (order_number),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Question rules for decision tree
CREATE TABLE question_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    condition_operator VARCHAR(20) NOT NULL,
    condition_value VARCHAR(50) NOT NULL,
    next_question_id INT,
    disease_id INT,
    confidence_score DECIMAL(3,2) DEFAULT 0.5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (next_question_id) REFERENCES questions(id) ON DELETE SET NULL,
    FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Questionnaire submissions
CREATE TABLE questionnaire_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, submission_date)
) ENGINE=InnoDB;

-- Questionnaire answers
CREATE TABLE questionnaire_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES questionnaire_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Diagnosis results
CREATE TABLE diagnosis_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    disease_id INT,
    confidence_score DECIMAL(3,2),
    diagnosis_text TEXT,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES questionnaire_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Statistics table
CREATE TABLE disease_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disease_id INT NOT NULL,
    region VARCHAR(100) DEFAULT 'Indonesia',
    percentage DECIMAL(5,2),
    total_cases INT,
    year INT,
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disease_id) REFERENCES diseases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin SheCare', 'admin@shecare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample diseases (bilingual)
INSERT INTO diseases (name_id, name_en, description_id, description_en, severity, recommendations_id, recommendations_en) VALUES
('Endometriosis', 'Endometriosis', 
'Kondisi dimana jaringan yang mirip dengan lapisan rahim tumbuh di luar rahim, menyebabkan nyeri panggul kronis dan masalah kesuburan.',
'A condition where tissue similar to the uterine lining grows outside the uterus, causing chronic pelvic pain and fertility issues.',
'high',
'Konsultasi dengan dokter kandungan segera, pemeriksaan USG transvaginal, terapi hormon, kemungkinan laparoskopi',
'Consult with a gynecologist immediately, transvaginal ultrasound examination, hormone therapy, possible laparoscopy'),

('Infeksi Jamur (Kandidiasis)', 'Yeast Infection (Candidiasis)',
'Infeksi vagina yang disebabkan oleh pertumbuhan berlebih jamur Candida albicans, menyebabkan gatal, iritasi, dan keputihan kental.',
'Vaginal infection caused by overgrowth of Candida albicans fungus, causing itching, irritation, and thick discharge.',
'low',
'Gunakan obat antijamur topikal atau oral, jaga kebersihan area kewanitaan, hindari pakaian ketat dan sintetis',
'Use topical or oral antifungal medication, maintain feminine hygiene, avoid tight and synthetic clothing'),

('Infeksi Bakteri (Bacterial Vaginosis)', 'Bacterial Infection (Bacterial Vaginosis)',
'Infeksi bakteri pada vagina yang menyebabkan keputihan dengan bau tidak sedap, gatal ringan.',
'Bacterial infection in the vagina causing discharge with unpleasant odor and mild itching.',
'moderate',
'Konsultasi dokter untuk antibiotik, jaga pH vagina dengan sabun khusus, hindari douching',
'Consult doctor for antibiotics, maintain vaginal pH with special soap, avoid douching'),

('PID (Penyakit Radang Panggul)', 'PID (Pelvic Inflammatory Disease)',
'Infeksi pada organ reproduksi wanita (rahim, tuba fallopi, ovarium) yang dapat menyebabkan nyeri panggul, demam, dan keputihan.',
'Infection of female reproductive organs (uterus, fallopian tubes, ovaries) that can cause pelvic pain, fever, and discharge.',
'high',
'SEGERA konsultasi dokter untuk antibiotik kuat, istirahat total, hindari hubungan seksual selama pengobatan',
'IMMEDIATELY consult doctor for strong antibiotics, complete rest, avoid sexual intercourse during treatment'),

('Normal', 'Normal',
'Tidak ditemukan indikasi penyakit yang signifikan berdasarkan gejala yang dilaporkan. Kondisi kesehatan reproduksi dalam batas normal.',
'No significant disease indication found based on reported symptoms. Reproductive health is within normal limits.',
'low',
'Pemeriksaan rutin setiap 6-12 bulan, jaga pola hidup sehat, konsumsi makanan bergizi, olahraga teratur',
'Routine checkup every 6-12 months, maintain healthy lifestyle, consume nutritious food, regular exercise');

-- Insert sample questions (bilingual)
INSERT INTO questions (question_text_id, question_text_en, question_type, min_value, max_value, order_number) VALUES
('Seberapa sering Anda mengalami nyeri panggul kronis dalam 3 bulan terakhir? (1=Tidak pernah, 5=Sangat sering/setiap hari)',
'How often have you experienced chronic pelvic pain in the last 3 months? (1=Never, 5=Very often/daily)',
'scale', 1, 5, 1),

('Apakah Anda mengalami keputihan abnormal (berbeda dari biasanya dalam hal warna, bau, atau tekstur)? (1=Tidak sama sekali, 5=Sangat parah)',
'Are you experiencing abnormal vaginal discharge (different from usual in color, smell, or texture)? (1=Not at all, 5=Very severe)',
'scale', 1, 5, 2),

('Apakah Anda merasakan gatal atau iritasi di area kewanitaan? (1=Tidak, 5=Sangat parah/tidak tertahankan)',
'Do you feel itching or irritation in the feminine area? (1=No, 5=Very severe/unbearable)',
'scale', 1, 5, 3),

('Apakah keputihan Anda disertai bau yang tidak sedap? (1=Tidak, 5=Sangat berbau)',
'Is your discharge accompanied by an unpleasant odor? (1=No, 5=Very odorous)',
'scale', 1, 5, 4),

('Seberapa parah rasa sakit saat menstruasi Anda? (1=Tidak sakit, 5=Sangat sakit hingga mengganggu aktivitas)',
'How severe is your menstrual pain? (1=No pain, 5=Very painful and disrupts activities)',
'scale', 1, 5, 5);

-- Insert sample statistics
INSERT INTO disease_statistics (disease_id, region, percentage, total_cases, year, source) VALUES
(1, 'Indonesia', 10.5, 2500000, 2024, 'Kementerian Kesehatan RI'),
(2, 'Indonesia', 15.3, 3600000, 2024, 'WHO Indonesia'),
(3, 'Indonesia', 12.8, 3000000, 2024, 'Kementerian Kesehatan RI'),
(4, 'Indonesia', 8.7, 2050000, 2024, 'WHO Indonesia');