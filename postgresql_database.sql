--EMPTY DATABASE;
--DROP SCHEMA PUBLIC CASCADE;
--CREATE SCHEMA public;

DROP TABLE IF EXISTS flashes;
DROP TABLE IF EXISTS qrcodes;
DROP TABLE IF EXISTS zones;
DROP TABLE IF EXISTS joueurs;
DROP TABLE IF EXISTS equipes;
DROP TABLE IF EXISTS couleurs_equipes;
DROP TABLE IF EXISTS parties;

CREATE TABLE parties (
	id_partie SERIAL NOT NULL,
	date_debut TIMESTAMP NOT NULL,
	date_fin TIMESTAMP NOT NULL,
	PRIMARY KEY (id_partie)
);

CREATE TABLE couleurs_equipes (
	id_couleur_equipe SERIAL NOT NULL,
	couleur VARCHAR(255) NOT NULL UNIQUE,
	PRIMARY KEY (id_couleur_equipe)
);

CREATE TABLE equipes (
	id_equipe SERIAL NOT NULL,
	partie INTEGER NOT NULL,
	couleur_equipe INTEGER NOT NULL,
	FOREIGN KEY (partie) REFERENCES parties (id_partie),
	FOREIGN KEY (couleur_equipe) REFERENCES couleurs_equipes (id_couleur_equipe),
	PRIMARY KEY (id_equipe)
);

CREATE TABLE joueurs (
	id_joueur SERIAL NOT NULL,
	identifiant VARCHAR(255) NOT NULL UNIQUE,
	equipe INTEGER NOT NULL,
	FOREIGN KEY (equipe) REFERENCES equipes (id_equipe),
	PRIMARY KEY (id_joueur)
);

CREATE TABLE zones (
	id_zone SERIAL NOT NULL,
	PRIMARY KEY (id_zone)
);

CREATE TABLE qrcodes (
	id_qrcode SERIAL NOT NULL,
	zone_ref INTEGER NOT NULL,
	FOREIGN KEY (zone_ref) REFERENCES zones (id_zone),
	PRIMARY KEY (id_qrcode)
);

CREATE TABLE flashes (
	id_flash SERIAL NOT NULL,
	date_flash TIMESTAMP NOT NULL,
	joueur INTEGER NOT NULL,
	qrcode INTEGER NOT NULL,
	FOREIGN KEY (joueur) REFERENCES joueurs (id_joueur),
	FOREIGN KEY (qrcode) REFERENCES qrcodes (id_qrcode),
	PRIMARY KEY (id_flash)
);

INSERT INTO parties (date_debut, date_fin) VALUES (NOW(), NOW());

INSERT INTO couleurs_equipes (couleur) VALUES ('Bleu');
INSERT INTO equipes (partie, couleur_equipe) VALUES (currval('parties_id_partie_seq'), currval('couleurs_equipes_id_couleur_equipe_seq'));
INSERT INTO joueurs (identifiant, equipe) VALUES ('joueur1', currval('equipes_id_equipe_seq'));

INSERT INTO couleurs_equipes (couleur) VALUES ('Jaune');
INSERT INTO equipes (partie, couleur_equipe) VALUES (currval('parties_id_partie_seq'), currval('couleurs_equipes_id_couleur_equipe_seq'));
INSERT INTO joueurs (identifiant, equipe) VALUES ('joueur2', currval('equipes_id_equipe_seq'));

INSERT INTO couleurs_equipes (couleur) VALUES ('Rouge');
INSERT INTO equipes (partie, couleur_equipe) VALUES (currval('parties_id_partie_seq'), currval('couleurs_equipes_id_couleur_equipe_seq'));
INSERT INTO joueurs (identifiant, equipe) VALUES ('joueur3', currval('equipes_id_equipe_seq'));

INSERT INTO couleurs_equipes (couleur) VALUES ('Vert');
INSERT INTO equipes (partie, couleur_equipe) VALUES (currval('parties_id_partie_seq'), currval('couleurs_equipes_id_couleur_equipe_seq'));
INSERT INTO joueurs (identifiant, equipe) VALUES ('joueur4', currval('equipes_id_equipe_seq'));

INSERT INTO zones DEFAULT VALUES;
INSERT INTO qrcodes (zone_ref) VALUES (currval('zones_id_zone_seq'));

INSERT INTO zones DEFAULT VALUES;
INSERT INTO qrcodes (zone_ref) VALUES (currval('zones_id_zone_seq'));
