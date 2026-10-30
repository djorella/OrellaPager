CREATE DATABASE IF NOT EXISTS orellapager;
USE orellapager;

CREATE TABLE IF NOT EXISTS users (
  uid INT AUTO_INCREMENT PRIMARY KEY,
  ip  VARCHAR(45) NOT NULL,
  UNIQUE KEY ux_users_ip (ip)
);

CREATE TABLE IF NOT EXISTS beeps (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  uid  INT NOT NULL,
  post TEXT NOT NULL,
  date DATETIME NOT NULL,
  KEY ix_beeps_uid (uid)
);

CREATE TABLE IF NOT EXISTS pings (
  uid    INT NOT NULL,
  beeper INT NOT NULL,
  PRIMARY KEY (uid, beeper)
);
