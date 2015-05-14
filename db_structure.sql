CREATE TABLE IF NOT EXISTS parties (
	id_partie INT NOT NULL AUTO_INCREMENT,
	nom VARCHAR(255),
	password VARCHAR(255),
	date_debut DATETIME NOT NULL,
	date_fin DATETIME NOT NULL,
	PRIMARY KEY (id_partie)
);

CREATE TABLE IF NOT EXISTS equipes (
	id_equipe INT NOT NULL AUTO_INCREMENT,
	hexcolor VARCHAR(10),
	PRIMARY KEY (id_equipe)
);

CREATE TABLE IF NOT EXISTS joueurs (
	id_joueur INT NOT NULL AUTO_INCREMENT,
	login VARCHAR(20) NOT NULL UNIQUE,
	PRIMARY KEY (id_joueur)
);

CREATE TABLE IF NOT EXISTS zones (
	id_zone INT NOT NULL AUTO_INCREMENT,
	nom_zone VARCHAR(20),
	PRIMARY KEY (id_zone)
);

CREATE TABLE IF NOT EXISTS qrcodes (
	id_qrcode INT NOT NULL AUTO_INCREMENT,
	zone INT NOT NULL,
	FOREIGN KEY (zone) REFERENCES zones (id_zone) ON DELETE CASCADE,
	PRIMARY KEY (id_qrcode)
);

CREATE TABLE IF NOT EXISTS inscriptions (
	id_inscription INT NOT NULL AUTO_INCREMENT,
	date_inscription DATETIME NOT NULL,
	partie INT NOT NULL,
	equipe INT NOT NULL,
	joueur INT NOT NULL,
	FOREIGN KEY (partie) REFERENCES parties (id_partie) ON DELETE CASCADE,
	FOREIGN KEY (equipe) REFERENCES equipes (id_equipe) ON DELETE CASCADE,
	FOREIGN KEY (joueur) REFERENCES joueurs (id_joueur) ON DELETE CASCADE,
	PRIMARY KEY (id_inscription)
);

CREATE TABLE IF NOT EXISTS flashs (
	id_flash INT NOT NULL AUTO_INCREMENT,
	date_flash DATETIME NOT NULL,
	joueur INT NOT NULL,
	qrcode INT NOT NULL,
	nbpoints INT NOT NULL,
	FOREIGN KEY (joueur) REFERENCES joueurs (id_joueur) ON DELETE CASCADE,
	FOREIGN KEY (qrcode) REFERENCES qrcodes (id_qrcode) ON DELETE CASCADE,
	PRIMARY KEY (id_flash)
);

CREATE OR REPLACE VIEW flashs_inscriptions AS
	SELECT f.*, (
		SELECT id_inscription
		FROM inscriptions i
		WHERE i.joueur = f.joueur
			AND i.date_inscription <= f.date_flash
		ORDER BY i.date_inscription DESC
		LIMIT 1
	) AS inscription
	FROM flashs f
	ORDER BY date_flash;

CREATE OR REPLACE VIEW infos_flashs AS
	SELECT f.id_flash AS id_flash, f.date_flash AS date_flash, (
		SELECT zone
		FROM qrcodes
		WHERE id_qrcode = f.qrcode
	) AS zone, f.qrcode AS qrcode, i.id_inscription AS id_inscription, i.partie AS partie, i.equipe AS equipe, i.joueur AS joueur, f.nbpoints AS nbpoints
	FROM flashs_inscriptions f, inscriptions i
	WHERE f.inscription = i.id_inscription
	ORDER BY date_flash;

CREATE OR REPLACE VIEW all_qrcodes AS
	SELECT p.id_partie AS partie, p.date_debut AS date_debut, p.date_fin AS date_fin, q.zone AS zone, q.id_qrcode AS qrcode, (
		SELECT id_flash
		FROM infos_flashs i
		WHERE p.id_partie = i.partie
			AND q.id_qrcode = i.qrcode
		ORDER BY date_flash DESC, id_flash DESC
		LIMIT 1
	) id_flash
	FROM parties p, qrcodes q
	ORDER BY p.id_partie, q.zone, q.id_qrcode;

<<<<<<< HEAD
INSERT INTO equipes (hexcolor) VALUES ('#FF0055');
INSERT INTO equipes (hexcolor) VALUES ('#0077DD');
INSERT INTO equipes (hexcolor) VALUES ('#00CC66');
INSERT INTO equipes (hexcolor) VALUES ('#EEEE22');

INSERT INTO zones (id_zone, nom_zone) VALUES (1, "Picasso");
INSERT INTO zones (id_zone, nom_zone) VALUES (2, "Phil");
INSERT INTO zones (id_zone, nom_zone) VALUES (3, "BU");
INSERT INTO zones (id_zone, nom_zone) VALUES (4, "6eme");

INSERT INTO qrcodes (zone) VALUES (1);
INSERT INTO qrcodes (zone) VALUES (2);
INSERT INTO qrcodes (zone) VALUES (3);
INSERT INTO qrcodes (zone) VALUES (4);

INSERT INTO joueurs (login) VALUES ("aperdria");
INSERT INTO joueurs (login) VALUES ("roccajos");
INSERT INTO joueurs (login) VALUES ("tricciol");
INSERT INTO joueurs (login) VALUES ("amatobap");

INSERT INTO `si28`.`parties` (`id_partie`, `nom`, `password`, `date_debut`, `date_fin`) VALUES (NULL, 'MaSuperPartie', 'test', '2015-02-01 00:00:00', '2015-05-01 00:00:00');

INSERT INTO `si28`.`parties` (`id_partie`, `nom`, `password`, `date_debut`, `date_fin`) VALUES (NULL, 'MaPartie2', '', '2015-03-01 00:00:00', '2015-04-01 00:00:00');

INSERT INTO `si28`.`inscriptions` (`id_inscription`, `date_inscription`, `partie`, `equipe`, `joueur`) VALUES (NULL, '2015-04-08 00:00:00', '1', '1', '1'), (NULL, '2015-04-13 00:00:00', '1', '2', '2'), (NULL, '2015-04-13 00:00:00', '1', '3', '3'), (NULL, '2015-04-13 00:00:00', '1', '4', '2');

INSERT INTO `si28`.`flashs` (`id_flash`, `date_flash`, `joueur`, `qrcode`, `nbpoints`) VALUES (NULL, '2015-05-01 00:00:00', '1', '1', '1'), (NULL, '2015-05-02 00:00:00', '2', '1', '1');
=======
INSERT INTO
	equipes (hexcolor)
VALUES
	('#FF0055'),
	('#0077DD'),
	('#00CC66'),
	('#EEEE22');

INSERT INTO
	zones (nom_zone)
VALUES
	('Picasso'),
	('Phil'),
	('BU'),
	('6eme');

INSERT INTO
	qrcodes (zone)
VALUES
	(1),
	(2),
	(3),
	(4);

INSERT INTO
	joueurs (login)
VALUES
	('aperdria'),
	('roccajos'),
	('tricciol'),
	('amatoba');

INSERT INTO
	parties (nom, password, date_debut, date_fin)
VALUES
	('Partie 1', NULL, '2015-02-01 00:00:00', '2015-07-01 00:00:00'),
	('Partie 2', NULL, '2015-03-01 00:00:00', '2015-08-01 00:00:00');

INSERT INTO
	inscriptions (date_inscription, partie, equipe, joueur)
VALUES
	('2015-04-08 00:00:00', '1', '1', '1'),
	('2015-04-08 00:00:00', '1', '2', '1'),
	('2015-04-08 00:00:00', '1', '3', '1'),
	('2015-04-08 00:00:00', '1', '4', '1');
>>>>>>> origin/master
