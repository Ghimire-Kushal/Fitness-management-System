-- ============================================================
--  GYM MANAGEMENT SYSTEM  -  SEED DATA
--  Owner: Person 1 (Database)
--  Run after schema.sql:  mysql -u root -p gym_db < seed.sql
--
--  Default logins (password shown in plain text here only for
--  your team; the DB stores a secure hash):
--    Admin   : admin@gym.com    / admin123
--    Trainer : ramesh@gym.com   / trainer123   (no login flow, see README)
--    Member  : sita@gym.com     / member123
-- ============================================================
USE gym_db;

-- Roles -------------------------------------------------------
INSERT INTO roles (role_name) VALUES ('Admin'), ('Trainer'), ('Member');

-- Users -------------------------------------------------------
-- role_id: 1=Admin, 2=Trainer, 3=Member
INSERT INTO users (full_name, email, password, phone, role_id) VALUES
('Gym Administrator', 'admin@gym.com',
 'scrypt:32768:8:1$1qEhyogSwqxLgmcS$5f0ebc835f0927ea32548f7bed83f7135d6a06ad567bf225be11efeeb3ce1b4fb2092dc7eaaf6a53dace21606c9a19311c43fee690adf4e5e0864cb9ad0221de',
 '9800000000', 1),
('Ramesh Thapa', 'ramesh@gym.com',
 'scrypt:32768:8:1$76qwjJeGKQ0gpQGp$4c528ab00edf31e6c85b7d4e316a88c01ed550501240e4f7cc18be65df1418c113aa85418f6136b49d7813f77832f03af8a4d2668590fdf264863d086eaaacee',
 '9811111111', 2),
('Sunita Gurung', 'sunita@gym.com',
 'scrypt:32768:8:1$76qwjJeGKQ0gpQGp$4c528ab00edf31e6c85b7d4e316a88c01ed550501240e4f7cc18be65df1418c113aa85418f6136b49d7813f77832f03af8a4d2668590fdf264863d086eaaacee',
 '9822222222', 2),
('Sita Sharma', 'sita@gym.com',
 'scrypt:32768:8:1$dIkxa7D5lCRl8cwO$0a9370dc881e5cbd827361560a2632ab728f914b79086f6af43fb5a96d02ac27afeedb3bca2c6a912b5ee9fd196c70bb3d23c624aefea4ea7ff1108a6689d264',
 '9833333333', 3);

-- Trainer profiles (link the two trainer users above) --------
INSERT INTO trainer_profiles (user_id, specialization, bio) VALUES
(2, 'Strength & Conditioning', 'Eight years coaching powerlifting and general strength.'),
(3, 'Yoga & Mobility',         'Certified yoga instructor focused on flexibility and recovery.');

-- Membership plans -------------------------------------------
INSERT INTO membership_plans (plan_name, duration_type, price, description) VALUES
('Monthly Basic',  'monthly', 1500.00, 'Full gym floor access for one month.'),
('Monthly Plus',   'monthly', 2500.00, 'Gym access plus group classes for one month.'),
('Yearly Basic',   'yearly', 15000.00, 'Full gym floor access for twelve months.'),
('Yearly Plus',    'yearly', 25000.00, 'Gym access plus group classes for twelve months.');

-- Time slots (a few sample sessions) -------------------------
INSERT INTO time_slots (slot_date, start_time, end_time, capacity) VALUES
(CURDATE() + INTERVAL 1 DAY, '06:00:00', '07:00:00', 10),
(CURDATE() + INTERVAL 1 DAY, '07:00:00', '08:00:00', 10),
(CURDATE() + INTERVAL 1 DAY, '17:00:00', '18:00:00', 8),
(CURDATE() + INTERVAL 2 DAY, '06:00:00', '07:00:00', 10),
(CURDATE() + INTERVAL 2 DAY, '18:00:00', '19:00:00', 8),
(CURDATE() + INTERVAL 3 DAY, '07:00:00', '08:00:00', 10);
