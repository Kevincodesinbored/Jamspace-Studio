-- Database: jamspace_db
CREATE DATABASE IF NOT EXISTS jamspace_db;
USE jamspace_db;

-- Table: users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    gender ENUM('Male', 'Female') DEFAULT NULL,
    dob DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: studios
CREATE TABLE studios (
    id CHAR(1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price_per_hour DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    description TEXT
);

-- Insert studio data
INSERT INTO studios (id, name, price_per_hour, image_path, description) VALUES
('A', 'Studio A', 7.00, 'assets/studio 1.jpeg', 'Perfect for small bands and solo practice'),
('B', 'Studio B', 9.00, 'assets/studio 2.jfif', 'Medium-sized studio with premium equipment'),
('C', 'Studio C', 11.00, 'assets/studio 3.jpg', 'Large professional studio for recording');

-- Table: bookings
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    studio_id CHAR(1) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    duration INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (studio_id) REFERENCES studios(id),
    INDEX idx_user_id (user_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status)
);

-- Table: cart (temporary storage before checkout)
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    studio_id CHAR(1) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    duration INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (studio_id) REFERENCES studios(id)
);

-- Table: sessions (for managing user login sessions)
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: reviews (for studio ratings and reviews)
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    studio_id CHAR(1) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (studio_id) REFERENCES studios(id),
    UNIQUE KEY unique_booking_review (booking_id),
    INDEX idx_studio_id (studio_id),
    INDEX idx_rating (rating)
);

-- Table: notifications (for automated notifications)
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT,
    type ENUM('booking_confirmed', 'booking_reminder', 'booking_completed', 'payment_confirmed', 'booking_cancelled') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Add average_rating column to studios table
ALTER TABLE studios ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00;
ALTER TABLE studios ADD COLUMN total_reviews INT DEFAULT 0;