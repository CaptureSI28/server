CREATE DATABASE IF NOT EXISTS si28;

USE si28;

CREATE TABLE IF NOT EXISTS couleurequipe (
  couleur_equipe varchar(20) NOT NULL UNIQUE,
  num_equipe int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (num_equipe)
);

CREATE TABLE IF NOT EXISTS partie (
  id_partie int NOT NULL AUTO_INCREMENT,
  date_debut date NOT NULL,
  date_fin date NOT NULL,
  PRIMARY KEY (id_partie)
);

CREATE TABLE IF NOT EXISTS equipe (
  id_equipe int NOT NULL AUTO_INCREMENT,
  partie int NOT NULL,
  equipe int NOT NULL,
  FOREIGN KEY (partie) REFERENCES partie(id_partie),
  FOREIGN KEY (equipe) REFERENCES couleurequipe(num_equipe),
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
  FOREIGN KEY (zone) REFERENCES zone(zone),
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

-- Insertions pour les tests

INSERT INTO couleurequipe (couleur_equipe) VALUES
('vert'),
('bleue'),
('rouge'),
('jaune');

INSERT INTO partie (date_debut, date_fin) VALUES (NOW(), NOW());

INSERT INTO equipe (partie, equipe) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4);

INSERT INTO joueur (login_joueur, equipe) VALUES
('joueur1', 1),
('joueur2', 2),
('joueur2', 3),
('joueur2', 4);

INSERT INTO zone () VALUES
(),
();

INSERT INTO qrcode (zone) VALUES
(1),
(2);
