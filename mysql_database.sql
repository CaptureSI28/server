CREATE DATABASE IF NOT EXISTS si28;

USE si28;

CREATE TABLE IF NOT EXISTS partie (
  id_partie int NOT NULL AUTO_INCREMENT,
  date_debut date NOT NULL,
  date_fin date NOT NULL,
  PRIMARY KEY (id_partie)
);

CREATE TABLE IF NOT EXISTS equipe (
  id_equipe int NOT NULL,
  PRIMARY KEY (id_equipe)
);

CREATE TABLE IF NOT EXISTS joueur (
  login_joueur varchar(20) NOT NULL,
  equipe int NOT NULL,
  FOREIGN KEY (equipe) REFERENCES equipe(id_equipe),
  PRIMARY KEY (login_joueur)
);

CREATE TABLE IF NOT EXISTS zone (
  id_zone int NOT NULL,
  PRIMARY KEY (id_zone)
);

CREATE TABLE IF NOT EXISTS qrcode (
  id_qrcode int NOT NULL,
  zone int NOT NULL,
  FOREIGN KEY (zone) REFERENCES zone(id_zone),
  PRIMARY KEY (id_qrcode)
);

CREATE TABLE IF NOT EXISTS flasher (
  date_flash date NOT NULL,
  joueur varchar(20) NOT NULL,
  qrcode int NOT NULL,
  FOREIGN KEY (joueur) REFERENCES joueur(login_joueur),
  FOREIGN KEY (qrcode) REFERENCES qrcode(id_qrcode),
  PRIMARY KEY (date_flash,joueur,qrcode)
);

CREATE TABLE IF NOT EXISTS inscription (
  id_inscription int NOT NULL,
  date_flash date NOT NULL,
  joueur varchar(20) NOT NULL,
  partie int NOT NULL,
  equipe int NOT NULL,
  FOREIGN KEY (joueur) REFERENCES joueur(login_joueur),
  FOREIGN KEY (partie) REFERENCES partie(id_partie),
  FOREIGN KEY (equipe) REFERENCES equipe(id_equipe),
  PRIMARY KEY (id_inscription)
);

-- Insertions pour les tests

INSERT INTO partie (date_debut, date_fin) VALUES (NOW(), NOW());

INSERT INTO equipe (id_equipe) VALUES
(1),
(2),
(3),
(4);

INSERT INTO joueur (login_joueur, equipe) VALUES
('joueur1', 1),
('joueur2', 2),
('joueur3', 3),
('joueur4', 4),
('joueur5', 1);

INSERT INTO zone (id_zone) VALUES
(1),
(2);

INSERT INTO qrcode (id_qrcode, zone) VALUES
(42, 1),
(43, 2);