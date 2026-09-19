
CREATE DATABASE IF NOT EXISTS ecole_db;
USE ecole_db;

CREATE TABLE IF NOT EXISTS eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) DEFAULT NULL,
    classe VARCHAR(20) NOT NULL,
    moyenne_trimestrielle DECIMAL(4, 2),
    moyenne_sequentielle DECIMAL(4, 2)
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);


INSERT INTO eleves (nom, prenom, classe, moyenne_trimestrielle, moyenne_sequentielle) VALUES 
('AFIDA', 'SARZEAU GAPDOUMOUN', 'term_ti', 14.50, 13.00),
('ASOFACK ANGE', 'INESS', 'term_ti', 16.00, 15.50),
('NDAM JOEAN', 'LEONCE', 'term_ti', 11.00, 12.50),
('KENOUE LETICIA', 'NDOKAS', 'term_ti', 11.00, 12.50),
('EGOLO FRANCIS', 'YVAN', 'term_ti', 11.00, 12.50),
('NKOUTOU IMRAN', 'FALLY', 'term_ti', 11.00, 12.50),
('NKOMIER NCHARE', 'FREDERIK LEONCE', 'term_ti', 11.00, 12.50),
('LONLAK NKECHAYA', 'EMMA DAVY', 'term_ti', 11.00, 12.50),
('LAMARE', 'RAOUF JALIL', 'term_ti', 11.00, 12.50),
('ABOUBA', 'ACHRAF ISMET', 'term_ti', 11.00, 12.50),
('MOULIOM BRAOULIN', 'LEWINGS', 'term_ti', 11.00, 12.50),
('MOUMBAGNA NCHARE', 'IBRAHIM SOUDESS', 'term_ti', 11.00, 12.50),
('MOUCHIGAM', 'LOIC CAREL', 'term_ti', 11.00, 12.50),
('MPEFAKUE', 'MOUBARACK', 'term_ti', 11.00, 12.50),
('NSAGOU', 'FEICAL ARAFAT', 'term_ti', 11.00, 12.50),
('NSAGOU NOUROU', 'HABIDIN', 'term_ti', 11.00, 12.50),
('NSAGOU', 'SADAM', 'term_ti', 11.00, 12.50),
('POUTOUGNIGNI MBOUBOU', 'WARRINN', 'term_ti', 11.00, 12.50),
('PEVETMI', 'MOISE', 'term_ti', 11.00, 12.50),
('RANE', 'HABIB BASSIR', 'term_ti', 11.00, 12.50);


