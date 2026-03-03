CREATE DATABASE challenge_hub;
USE challenge_hub;

CREATE TABLE user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(25) NOT NULL,
    prenom VARCHAR(25) NOT NULL,
    email VARCHAR(50) UNIQUE NOT NULL,
    motdepasse VARCHAR(255) NOT NULL
);