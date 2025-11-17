CREATE DATABASE hms_db;--admin table
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role VARCHAR(20) DEFAULT 'admin'
);
--values for admin
INSERT INTO admin (full_name, email, password) VALUES ('Admin User', 'admin@hms.com', MD5('admin123'));

--patient table
CREATE TABLE patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255),
  d_o_b DATE NOT NULL,
  gender VARCHAR(20) NOT NULL,
  blood_group VARCHAR(10) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(150) NOT NULL,
  doctor VARCHAR(100) NOT NULL,
  role VARCHAR(20) DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--values for patient
INSERT INTO patients (full_name, email, password, d_o_b, gender, blood_group, phone, address, doctor)
VALUES
('Alice Williams', 'alice.williams@example.com', MD5('password123'), '1993-05-15', 'Female', 'A+', '123-456-7890', '123 Main St, Anytown, USA', 'Dr. John Smith'),
('Bob Johnson', 'bob.johnson@example.com', MD5('password123'), '1983-08-20', 'Male', 'B+', '123-456-7891', '456 Elm St, Anytown, USA', 'Dr. Emily Johnson'),
('Charlie Brown', 'charlie.brown@example.com', MD5('password123'), '1988-12-10', 'Male', 'O+', '123-456-7892', '789 Oak St, Anytown, USA', 'Dr. Michael Brown'),
('David Wilson', 'david.wilson@example.com', MD5('password123'), '1990-11-25', 'Male', 'AB+', '123-456-7893', '101 Pine St, Anytown, USA', 'Dr. Sarah Lee'),
('Eva Martinez', 'eva.martinez@example.com', MD5('password123'), '1992-07-18', 'Female', 'O-', '123-456-7894', '202 Maple St, Anytown, USA', 'Dr. John Smith'),
('Frank Thomas', 'frank.thomas@example.com', MD5('password123'), '1985-02-14', 'Male', 'B-', '123-456-7895', '404 Cedar St, Anytown, USA', 'Dr. Emily Johnson');

-- doctors table
CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255),
  d_o_b DATE NOT NULL,
  gender VARCHAR(20) NOT NULL,
  department VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(150) NOT NULL,
  status VARCHAR(100) NOT NULL,
  role VARCHAR(20) DEFAULT 'docotor',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Values for doctors table
INSERT INTO doctors (full_name, email, password, d_o_b, gender, department, phone, address, status)
VALUES
('Dr. John Smith', 'john.smith@example.com', MD5('password123'), '1978-01-15', 'Male', 'Cardiology', '123-456-7890', '123 Main St, Anytown, USA', 'On Call'),
('Dr. Emily Johnson', 'emily.johnson@example.com', MD5('password123'), '1985-03-22', 'Female', 'Neurology', '123-456-7891', '456 Elm St, Anytown, USA', 'On Call'),
('Dr. Michael Brown', 'michael.brown@example.com', MD5('password123'), '1973-07-30', 'Male', 'Orthopedics', '123-456-7892', '789 Oak St, Anytown, USA', 'On Call'),
('Dr. Sarah Lee', 'sarah.lee@example.com', MD5('password123'), '1980-09-15', 'Female', 'Pediatrics', '123-456-7893', '101 Pine St, Anytown, USA', 'On Call'),
('Dr. David Kim', 'david.kim@example.com', MD5('password123'), '1975-04-10', 'Male', 'Dermatology', '123-456-7894', '202 Maple St, Anytown, USA', 'On Call'),
('Dr. Laura Garcia', 'laura.garcia@example.com', MD5('password123'), '1982-06-25', 'Female', 'Gynecology', '123-456-7895', '303 Birch St, Anytown, USA', 'On Call');

-- Table for appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(100) NOT NULL,
    patient_email VARCHAR(100) NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    appointment_date DATE,
    appointment_time TIME,
    description VARCHAR(255),
    status VARCHAR(100) NOT NULL,
    reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
    reminder_sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- values for appointment table
INSERT INTO appointments (patient_name, patient_email, doctor_name, description, appointment_date, appointment_time, reminder_sent, reminder_sent_at, status)
VALUES
('Alice Williams', 'alice.williams@example.com', 'Dr. John Smith', 'Regular check-up', '2023-09-15', '10:00:00', 0, NULL, 'pending'),
('Bob Johnson', 'bob.johnson@example.com', 'Dr. Emily Johnson', 'Follow-up visit', '2023-09-16', '11:00:00', 0, NULL, 'pending'),
('Charlie Brown', 'charlie.brown@example.com', 'Dr. Michael Brown', 'Consultation', '2023-09-17', '12:00:00', 0, NULL, 'pending'),
('David Wilson', 'david.wilson@example.com', 'Dr. Sarah Lee', 'Routine check', '2023-09-18', '09:30:00', 0, NULL, 'pending'),
('Eva Martinez', 'eva.martinez@example.com', 'Dr. John Smith', 'Flu symptoms', '2023-09-19', '14:00:00', 0, NULL, 'pending'),
('Frank Thomas', 'frank.thomas@example.com', 'Dr. Emily Johnson', 'Back pain', '2023-09-20', '15:30:00', 0, NULL, 'pending');

--medical records table
CREATE TABLE medical_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  patient_name VARCHAR(100),
  doctor_name VARCHAR(100),
  date DATE,
  diagnosis VARCHAR(255),
  treatment VARCHAR(255),
  doctor_notes TEXT,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

--values for medical records
INSERT INTO medical_records (patient_id, patient_name, doctor_name, date, diagnosis, treatment, doctor_notes)
VALUES
(1, 'Alice Williams', 'Dr. John Smith', '2023-08-10', 'Flu', 'Rest and hydration', 'Patient advised to rest and drink plenty of fluids.'),
(2, 'Bob Johnson', 'Dr. Emily Johnson', '2023-08-12', 'Back Pain', 'Physical therapy', 'Recommended physical therapy sessions.'),
(3, 'Charlie Brown', 'Dr. Michael Brown', '2023-08-15', 'Allergy', 'Antihistamines', 'Prescribed antihistamines for allergy relief.'),
(4, 'David Wilson', 'Dr. Sarah Lee', '2023-08-18', 'Sprained Ankle', 'Ice and elevation', 'Advised to apply ice and elevate the ankle.'),
(5, 'Eva Martinez', 'Dr. John Smith', '2023-08-20', 'Stomach Ache', 'Dietary changes', 'Suggested dietary modifications to alleviate symptoms.'),
(6, 'Frank Thomas', 'Dr. Emily Johnson', '2023-08-22', 'Headache', 'Pain relievers', 'Recommended over-the-counter pain relievers.'),
(1, 'Alice Williams', 'Dr. John Smith', '2023-09-05', 'Seasonal Allergies', 'Antihistamines', 'Patient reports improvement with current medication.'),
(2, 'Bob Johnson', 'Dr. Emily Johnson', '2023-09-07', 'Lower Back Pain', 'Physical therapy and exercises', 'Patient advised to continue physical therapy and perform prescribed exercises at home.'),
(3, 'Charlie Brown', 'Dr. Michael Brown', '2023-09-10', 'Skin Rash', 'Topical cream', 'Prescribed a topical cream to be applied twice daily.'),
(4, 'David Wilson', 'Dr. Sarah Lee', '2023-09-12', 'Common Cold', 'Rest and fluids', 'Advised patient to rest and stay hydrated.'),
(5, 'Eva Martinez', 'Dr. John Smith', '2023-09-14', 'Migraine', 'Pain management plan', 'Discussed a pain management plan including medication and lifestyle changes.'),
(6, 'Frank Thomas', 'Dr. Emily Johnson', '2023-09-16', 'Tension Headache', 'Stress reduction techniques', 'Recommended stress reduction techniques and regular exercise.');

--prescription table
CREATE TABLE prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  doctor_id INT,
  patient_name VARCHAR(100),
  doctor_name VARCHAR(100),
  drug_name VARCHAR(255),
  dosage VARCHAR(100),
  price INT(10),
  duration VARCHAR(100)
);

ALTER TABLE prescriptions ADD COLUMN date_added DATE DEFAULT CURRENT_DATE;
--values for prescription table
INSERT INTO prescriptions (patient_id, patient_name, doctor_name, drug_name, dosage, price, duration)
VALUES
(1, 'Alice Williams', 'Dr. John Smith', 'Amoxicillin', '500mg', 20, '7 days'),
(2, 'Bob Johnson', 'Dr. Emily Johnson', 'Ibuprofen', '200mg', 15, '5 days'),
(3, 'Charlie Brown', 'Dr. Michael Brown', 'Loratadine', '10mg', 10, '10 days'),
(4, 'David Wilson', 'Dr. Sarah Lee', 'Acetaminophen', '500mg', 12, '3 days'),
(5, 'Eva Martinez', 'Dr. John Smith', 'Omeprazole', '20mg', 25, '14 days'),
(6, 'Frank Thomas', 'Dr. Emily Johnson',      'Naproxen', '250mg', 18, '7 days'),
(1, 'Alice Williams', 'Dr. John Smith', 'Cetirizine', '10mg', 10, '10 days'),
(2, 'Bob Johnson', 'Dr. Emily Johnson', 'Muscle Relaxant', '50mg', 22, '5 days'),
(3, 'Charlie Brown', 'Dr. Michael Brown', 'Hydrocortisone Cream', '1%', 8, '14 days'),
(4, 'David Wilson', 'Dr. Sarah Lee', 'Cough Syrup', '10ml', 15, '7 days'),
(5, 'Eva Martinez', 'Dr. John Smith', 'Sumatriptan', '50mg', 30, 'As needed'),
(6, 'Frank Thomas', 'Dr. Emily Johnson', 'Magnesium Supplements', '400mg', 12, '30 days');
--doctor reports table
CREATE TABLE doctor_reports (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT(11) NOT NULL,
  patient_id INT(11) NOT NULL,
  doctor_name VARCHAR(100) NOT NULL,
  patient_name VARCHAR(100) NOT NULL,
  diagnosis TEXT NOT NULL,
  treatment TEXT NOT NULL,
  report_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);
