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
  age INT NOT NULL,
  gender VARCHAR(20) NOT NULL,
  blood_group VARCHAR(10) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(150) NOT NULL,
  doctor VARCHAR(100) NOT NULL,
  role VARCHAR(20) DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--values for patient
INSERT INTO patients (full_name, email, password, age, gender, blood_group, phone, address, doctor)
VALUES
('Alice Williams', 'alice.williams@example.com', MD5('password123'), 30, 'Female', 'A+', '123-456-7890', '123 Main St, Anytown, USA', 'Dr. John Smith'),
('Bob Johnson', 'bob.johnson@example.com', MD5('password123'), 40, 'Male', 'B+', '123-456-7891', '456 Elm St, Anytown, USA', 'Dr. Emily Johnson'),
('Charlie Brown', 'charlie.brown@example.com', MD5('password123'), 35, 'Male', 'O+', '123-456-7892', '789 Oak St, Anytown, USA', 'Dr. Michael Brown');

-- doctors table
CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(255),
  age INT NOT NULL,
  gender VARCHAR(20) NOT NULL,
  department VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(150) NOT NULL,
  status VARCHAR(100) NOT NULL,
  role VARCHAR(20) DEFAULT 'docotor',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Values for doctors table
INSERT INTO doctors (full_name, email, password, age, gender, department, phone, address, status)
VALUES
('Dr. John Smith', 'john.smith@example.com', MD5('password123'), 45, 'Male', 'Cardiology', '123-456-7890', '123 Main St, Anytown, USA', 'On Call'),
('Dr. Emily Johnson', 'emily.johnson@example.com', MD5('password123'), 38, 'Female', 'Neurology', '123-456-7891', '456 Elm St, Anytown, USA', 'On Call'),
('Dr. Michael Brown', 'michael.brown@example.com', MD5('password123'), 50, 'Male', 'Orthopedics', '123-456-7892', '789 Oak St, Anytown, USA', 'On Call');

-- Table for appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    patient_name VARCHAR(100) NOT NULL,
    patient_email VARCHAR(100) NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    appointment_date DATE,
    appointment_time TIME,
    description VARCHAR(255),
    status VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- values for appointment table
INSERT INTO appointments (patient_name, patient_email, doctor_name, description, appointment_date, appointment_time, status)
VALUES
('Alice Williams', 'alice.williams@example.com', 'Dr. John Smith', 'Regular check-up', '2023-09-15', '10:00:00', 'pending'),
('Bob Johnson', 'bob.johnson@example.com', 'Dr. Emily Johnson', 'Follow-up visit', '2023-09-16', '11:00:00', 'pending'),
('Charlie Brown', 'charlie.brown@example.com', 'Dr. Michael Brown', 'Consultation', '2023-09-17', '12:00:00', 'pending');

--medical records table
CREATE TABLE medical_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  date DATE,
  diagnosis VARCHAR(255),
  treatment VARCHAR(255),
  doctor_notes TEXT,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

--prescription table
CREATE TABLE prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT,
  drug_name VARCHAR(255),
  dosage VARCHAR(100),
  duration VARCHAR(100)
);

ALTER TABLE prescriptions ADD COLUMN date_added DATE DEFAULT CURRENT_DATE;

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
