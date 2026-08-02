USE travel_agency;

CREATE TABLE IF NOT EXISTS customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS packages (
    package_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    duration_days INT UNSIGNED NOT NULL,
    available_from DATE NOT NULL,
    available_to DATE NOT NULL,
    available_slots INT UNSIGNED NOT NULL DEFAULT 0,
    image_url VARCHAR(2048) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    number_of_people INT UNSIGNED NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    travel_date DATE NOT NULL,
    special_requests TEXT DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id)
        REFERENCES customers(customer_id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_package FOREIGN KEY (package_id)
        REFERENCES packages(package_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    provider VARCHAR(30) NOT NULL DEFAULT 'esewa',
    purchase_order_id VARCHAR(100) NOT NULL UNIQUE,
    pidx VARCHAR(100) DEFAULT NULL UNIQUE,
    transaction_id VARCHAR(100) DEFAULT NULL UNIQUE,
    amount_paisa BIGINT UNSIGNED NOT NULL,
    status ENUM('initiated', 'pending', 'completed', 'failed', 'cancelled', 'expired', 'refunded') NOT NULL DEFAULT 'initiated',
    provider_status VARCHAR(50) DEFAULT NULL,
    failure_message VARCHAR(500) DEFAULT NULL,
    initiated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_payment_booking (booking_id),
    INDEX idx_payment_status (status)
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_users (username, email, password)
VALUES ('admin', 'admin@travel.local', 'admin123')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO packages
    (package_name, destination, description, price, duration_days,
     available_from, available_to, available_slots, status)
SELECT *
FROM (
    SELECT 'Kathmandu Heritage Tour', 'Kathmandu Valley',
           'Explore ancient temples and UNESCO World Heritage Sites around Kathmandu.',
           15000.00, 3, '2026-08-01', '2027-06-30', 20, 'active'
    UNION ALL
    SELECT 'Pokhara Adventure Package', 'Pokhara',
           'Experience paragliding, boating on Phewa Lake, caves, and Himalayan views.',
           25000.00, 5, '2026-08-01', '2027-06-30', 15, 'active'
    UNION ALL
    SELECT 'Everest Base Camp Trek', 'Solukhumbu',
           'Trek to Everest Base Camp with guides, accommodation, meals, and permits.',
           125000.00, 14, '2026-09-01', '2027-05-31', 10, 'active'
    UNION ALL
    SELECT 'Chitwan Jungle Safari', 'Chitwan National Park',
           'A wildlife safari with jungle walks, canoe rides, and a Tharu cultural program.',
           18000.00, 2, '2026-08-01', '2027-06-30', 30, 'active'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM packages);

INSERT INTO packages
    (package_name, destination, description, price, duration_days,
     available_from, available_to, available_slots, status)
SELECT
    'Payment Sandbox Test', 'Sandbox',
    'A low-value package used only to test the sandbox payment workflow.',
    20.00, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR), 100, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM packages WHERE package_name = 'Payment Sandbox Test'
);
