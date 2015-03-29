CREATE TABLE IF NOT EXISTS parties (
	id_partie INT NOT NULL AUTO_INCREMENT,
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
	PRIMARY KEY (id_zone)
);

CREATE TABLE IF NOT EXISTS qrcodes (
	id_qrcode INT NOT NULL AUTO_INCREMENT,
	zone INT NOT NULL,
	FOREIGN KEY (zone) REFERENCES zones (id_zone),
	PRIMARY KEY (id_qrcode)
);

CREATE TABLE IF NOT EXISTS inscriptions (
	id_inscription INT NOT NULL AUTO_INCREMENT,
	date_inscription DATETIME NOT NULL,
	partie INT NOT NULL,
	equipe INT NOT NULL,
	joueur INT NOT NULL,
	FOREIGN KEY (partie) REFERENCES parties (id_partie),
	FOREIGN KEY (equipe) REFERENCES equipes (id_equipe),
	FOREIGN KEY (joueur) REFERENCES joueurs (id_joueur),
	PRIMARY KEY (id_inscription)
);

CREATE TABLE IF NOT EXISTS flashs (
	id_flash INT NOT NULL AUTO_INCREMENT,
	date_flash DATETIME NOT NULL,
	joueur INT NOT NULL,
	qrcode INT NOT NULL,
	FOREIGN KEY (joueur) REFERENCES joueurs (id_joueur),
	FOREIGN KEY (qrcode) REFERENCES qrcodes (id_qrcode),
	PRIMARY KEY (id_flash)
);

INSERT INTO equipes (hexcolor) VALUES ('#FF0055');
INSERT INTO equipes (hexcolor) VALUES ('#0077DD');
INSERT INTO equipes (hexcolor) VALUES ('#00CC66');
INSERT INTO equipes (hexcolor) VALUES ('#EEEE22');
