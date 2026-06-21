DROP DATABASE IF EXISTS gym_db;
CREATE DATABASE gym_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gym_db;

-- ------------------------------------------------------------
-- Roles: Admin, Trainer, Member  (role-based access)
-- ------------------------------------------------------------
CREATE TABLE roles (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(20) NOT NULL UNIQUE
);

-- ------------------------------------------------------------
-- Users: every person in the system (login + profile)
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- stores a hash, never plain text
    phone      VARCHAR(20),
    role_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ------------------------------------------------------------
-- Membership plans the member can choose (monthly / yearly)
-- ------------------------------------------------------------
CREATE TABLE membership_plans (
    plan_id       INT AUTO_INCREMENT PRIMARY KEY,
    plan_name     VARCHAR(60) NOT NULL,
    duration_type ENUM('monthly','yearly') NOT NULL,
    price         DECIMAL(10,2) NOT NULL,
    description   VARCHAR(255)
);

-- ------------------------------------------------------------
-- A member's chosen membership (online admission / registration)
-- ------------------------------------------------------------
CREATE TABLE memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    plan_id       INT NOT NULL,
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    status        ENUM('active','expired') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(plan_id)
);

-- ------------------------------------------------------------
-- Extra details for users who are trainers
-- ------------------------------------------------------------
CREATE TABLE trainer_profiles (
    trainer_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    specialization VARCHAR(100),
    bio            VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Admin assigns a trainer to a member
-- ------------------------------------------------------------
CREATE TABLE member_trainers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    member_id   INT NOT NULL,
    trainer_id  INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (member_id, trainer_id),
    FOREIGN KEY (member_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainer_profiles(trainer_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Bookable time slots (used for both gym sessions and
-- trainer appointments).  capacity = how many can book it.
-- ------------------------------------------------------------
CREATE TABLE time_slots (
    slot_id    INT AUTO_INCREMENT PRIMARY KEY,
    slot_date  DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time   TIME NOT NULL,
    capacity   INT NOT NULL DEFAULT 10
);

-- ------------------------------------------------------------
-- Bookings made by members.
--  booking_type = 'gym_session'  -> trainer_id is NULL
--  booking_type = 'appointment'  -> trainer_id is set
--  UNIQUE rule stops the same member double-booking one slot.
-- ------------------------------------------------------------
CREATE TABLE bookings (
    booking_id   INT AUTO_INCREMENT PRIMARY KEY,
    member_id    INT NOT NULL,
    slot_id      INT NOT NULL,
    trainer_id   INT NULL,
    booking_type ENUM('gym_session','appointment') NOT NULL,
    status       ENUM('pending','approved','completed') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (member_id, slot_id, booking_type),
    FOREIGN KEY (member_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id)    REFERENCES time_slots(slot_id),
    FOREIGN KEY (trainer_id) REFERENCES trainer_profiles(trainer_id)
);

-- ------------------------------------------------------------
-- Workout plans created for a member (optionally tied to the
-- trainer they were assigned). Created from the admin side.
-- ------------------------------------------------------------
CREATE TABLE workout_plans (
    plan_id     INT AUTO_INCREMENT PRIMARY KEY,
    member_id   INT NOT NULL,
    trainer_id  INT NULL,
    title       VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainer_profiles(trainer_id)
);
