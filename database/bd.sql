CREATE TABLE espece(
id_espece NUMBER,
nom_latin VARCHAR2(100),
nom_usuel VARCHAR2(100),
menacee NUMBER(1) CHECK(menacee IN(0,1)),
CONSTRAINT pk_espece PRIMARY KEY(id_espece)
);

CREATE TABLE zone(
id_zone NUMBER NOT NULL,
nom_zone VARCHAR2(100),
CONSTRAINT pk_zone PRIMARY KEY(id_zone)
);

CREATE TABLE parraine(
id_parrainage NUMBER NOT NULL,
nom VARCHAR2(100),
prenom VARCHAR2(100),
CONSTRAINT pk_parraine PRIMARY KEY(id_parrainage)
);

CREATE TABLE soin(
id_soin NUMBER NOT NULL,
type_soin VARCHAR2(50),
libelle VARCHAR2(255),
CONSTRAINT pk_soin PRIMARY KEY(id_soin)
);

CREATE TABLE nourriture(
id_nourriture NUMBER NOT NULL,
dose_journaliere NUMBER(10,2),
type_nourriture VARCHAR2(50),
CONSTRAINT pk_nourriture PRIMARY KEY(id_nourriture)
);

CREATE TABLE particularite(
id_particularite NUMBER NOT NULL,
nom_particularite VARCHAR2(100),
CONSTRAINT pk_particularite PRIMARY KEY(id_particularite)
);

CREATE TABLE reparation(
id_reparation NUMBER NOT NULL,
nature VARCHAR2(50),
libelle VARCHAR2(50),
CONSTRAINT pk_reparation PRIMARY KEY(id_reparation)
);

CREATE TABLE prestataires(
id_prestataire NUMBER NOT NULL,
contact VARCHAR2(100),
CONSTRAINT pk_prestataire PRIMARY KEY(id_prestataire)
);

CREATE TABLE calendrier_ca(
date_jour DATE NOT NULL,
CONSTRAINT pk_calendrier PRIMARY KEY(date_jour)
);

CREATE TABLE enclos(
id_enclos NUMBER NOT NULL,
latitude NUMBER(9,6),
longitude NUMBER(9,6),
surface NUMBER(10,2),
id_zone NUMBER,
CONSTRAINT pk_enclos PRIMARY KEY(id_enclos),
CONSTRAINT fk_enclos_zone FOREIGN KEY(id_zone) REFERENCES zone(id_zone)
);

CREATE TABLE animal(
id_animal NUMBER NOT NULL,
nom VARCHAR2(100),
poids NUMBER(10,2),
date_naissance DATE,
regime_alimentaire VARCHAR2(100),
id_animal_1 NUMBER,
id_enclos NUMBER,
id_animal_2 NUMBER,
id_espece NUMBER,
CONSTRAINT pk_animal PRIMARY KEY(id_animal),
CONSTRAINT fk_an_parent1 FOREIGN KEY(id_animal_1) REFERENCES animal(id_animal),
CONSTRAINT fk_an_parent2 FOREIGN KEY(id_animal_2) REFERENCES animal(id_animal),
CONSTRAINT fk_an_enclos FOREIGN KEY(id_enclos) REFERENCES enclos(id_enclos),
CONSTRAINT fk_an_espece FOREIGN KEY(id_espece) REFERENCES espece(id_espece)
);

CREATE TABLE personnel(
id_personnel VARCHAR2(10) NOT NULL,
nom VARCHAR2(100),
prenom VARCHAR2(100),
mot_de_pass VARCHAR2(255),
date_entree DATE,
salaire NUMBER(10,2),
fonction VARCHAR2(100),
id_personnel_1 VARCHAR2(10),
id_personnel_2 VARCHAR2(10),
id_zone NUMBER,
CONSTRAINT pk_personnel PRIMARY KEY(id_personnel),
CONSTRAINT fk_pers_sup1 FOREIGN KEY(id_personnel_1) REFERENCES personnel(id_personnel),
CONSTRAINT fk_pers_sup2 FOREIGN KEY(id_personnel_2) REFERENCES personnel(id_personnel),
CONSTRAINT fk_pers_zone FOREIGN KEY(id_zone) REFERENCES zone(id_zone)
);

CREATE TABLE boutique(
id_boutique NUMBER NOT NULL,
nom_boutique VARCHAR2(100),
id_zone NUMBER,
CONSTRAINT pk_boutique PRIMARY KEY(id_boutique),
CONSTRAINT fk_bout_zone FOREIGN KEY(id_zone) REFERENCES zone(id_zone)
);

CREATE TABLE cohabiter(
id_espece NUMBER,
id_espece_1 NUMBER,
CONSTRAINT pk_cohabiter PRIMARY KEY(id_espece,id_espece_1),
CONSTRAINT fk_cohab_esp1 FOREIGN KEY(id_espece) REFERENCES espece(id_espece),
CONSTRAINT fk_cohab_esp2 FOREIGN KEY(id_espece_1) REFERENCES espece(id_espece)
);

CREATE TABLE parrainage(
id_animal NUMBER,
id_parrainage NUMBER,
niveau VARCHAR2(50),
prestation VARCHAR2(200),
CONSTRAINT pk_parrainage PRIMARY KEY(id_animal,id_parrainage),
CONSTRAINT fk_parr_ani FOREIGN KEY(id_animal) REFERENCES animal(id_animal),
CONSTRAINT fk_parr_par FOREIGN KEY(id_parrainage) REFERENCES parraine(id_parrainage)
);

CREATE TABLE soigner(
id_animal NUMBER,
id_personnel VARCHAR2(10),
id_soin NUMBER,
date_intervention DATE,
est_attitre NUMBER(1) CHECK(est_attitre IN(0,1)),
CONSTRAINT pk_soigner PRIMARY KEY(id_animal,id_personnel,id_soin,date_intervention),
CONSTRAINT fk_soig_ani FOREIGN KEY(id_animal) REFERENCES animal(id_animal),
CONSTRAINT fk_soig_pers FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel),
CONSTRAINT fk_soig_soin FOREIGN KEY(id_soin) REFERENCES soin(id_soin)
);

CREATE TABLE nourrir(
id_animal NUMBER,
id_personnel VARCHAR2(10),
id_nourriture NUMBER,
date_nourrissage DATE,
CONSTRAINT pk_nourrir PRIMARY KEY(id_animal,id_personnel,id_nourriture,date_nourrissage),
CONSTRAINT fk_nour_ani FOREIGN KEY(id_animal) REFERENCES animal(id_animal),
CONSTRAINT fk_nour_pers FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel),
CONSTRAINT fk_nour_nour FOREIGN KEY(id_nourriture) REFERENCES nourriture(id_nourriture)
);

CREATE TABLE possede(
id_enclos NUMBER NOT NULL,
id_particularite NUMBER NOT NULL,
CONSTRAINT pk_possede PRIMARY KEY(id_enclos,id_particularite),
CONSTRAINT fk_poss_enc FOREIGN KEY(id_enclos) REFERENCES enclos(id_enclos),
CONSTRAINT fk_poss_part FOREIGN KEY(id_particularite) REFERENCES particularite(id_particularite)
);

CREATE TABLE faite(
id_enclos NUMBER NOT NULL,
id_reparation NUMBER NOT NULL,
CONSTRAINT pk_faite PRIMARY KEY(id_enclos,id_reparation),
CONSTRAINT fk_faite_enc FOREIGN KEY(id_enclos) REFERENCES enclos(id_enclos),
CONSTRAINT fk_faite_rep FOREIGN KEY(id_reparation) REFERENCES reparation(id_reparation)
);

CREATE TABLE realise(
id_reparation NUMBER NOT NULL,
id_prestataire NUMBER NOT NULL,
CONSTRAINT pk_realise PRIMARY KEY(id_reparation,id_prestataire),
CONSTRAINT fk_real_rep FOREIGN KEY(id_reparation) REFERENCES reparation(id_reparation),
CONSTRAINT fk_real_pres FOREIGN KEY(id_prestataire) REFERENCES prestataires(id_prestataire)
);

CREATE TABLE personnel_technique(
id_personnel VARCHAR2(10) NOT NULL,
id_reparation NUMBER NOT NULL,
CONSTRAINT pk_pers_tech PRIMARY KEY(id_personnel,id_reparation),
CONSTRAINT fk_tech_pers FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel),
CONSTRAINT fk_tech_rep FOREIGN KEY(id_reparation) REFERENCES reparation(id_reparation)
);

CREATE TABLE employe_boutique(
id_personnel VARCHAR2(10) NOT NULL,
id_boutique NUMBER NOT NULL,
est_responsable NUMBER(1) CHECK(est_responsable IN(0,1)),
CONSTRAINT pk_emp_bout PRIMARY KEY(id_personnel,id_boutique),
CONSTRAINT fk_ebout_pers FOREIGN KEY(id_personnel) REFERENCES personnel(id_personnel),
CONSTRAINT fk_ebout_bout FOREIGN KEY(id_boutique) REFERENCES boutique(id_boutique)
);

CREATE TABLE ca_journalier(
id_boutique NUMBER NOT NULL,
date_ca  DATE NOT NULL,
chiffre_affaire NUMBER(15,2),
CONSTRAINT pk_ca_jour PRIMARY KEY(id_boutique,date_ca),
CONSTRAINT fk_caj_bout FOREIGN KEY(id_boutique) REFERENCES boutique(id_boutique),
CONSTRAINT fk_caj_cal FOREIGN KEY(date_ca) REFERENCES calendrier_ca(date_jour)
);

INSERT INTO espece VALUES(1,'Panthera leo','Lion d''Afrique',1);
INSERT INTO espece VALUES(2,'Ursus arctos horribilis','Grizzly',1);
INSERT INTO espece VALUES(3,'Aquila chrysaetos','Aigle royal',0);
INSERT INTO zone VALUES(1,'Zone des Félins');
INSERT INTO zone VALUES(2,'Zone des Rapaces');
INSERT INTO zone VALUES(3,'Zone Nord-Américaine');
INSERT INTO parraine VALUES(1,'Dupont','Jean');
INSERT INTO parraine VALUES(2,'Lefebvre','Marie');
INSERT INTO soin VALUES(1,'Vaccination','Rappel annuel rage');
INSERT INTO soin VALUES(2,'Examen','Contrôle dentaire');
INSERT INTO soin VALUES(3,'Brossage','Brossage');
INSERT INTO nourriture VALUES(1,5.50,'Viande');
INSERT INTO nourriture VALUES(2,2.00,'Granulés');
INSERT INTO particularite VALUES(1,'Bassin d''eau douce');
INSERT INTO particularite VALUES(2,'Aménagement grizzly');
INSERT INTO reparation VALUES(1,'Gros','Soudure clôture');
INSERT INTO reparation VALUES(2,'Petit','Nettoyage bassin');
INSERT INTO prestataires VALUES(101,'contact@prestataire.com');
INSERT INTO calendrier_ca VALUES(TO_DATE('2026-03-01','YYYY-MM-DD'));
INSERT INTO calendrier_ca VALUES(TO_DATE('2026-03-02','YYYY-MM-DD'));
INSERT INTO enclos VALUES(10,46.123,2.001,500.00,1);
INSERT INTO enclos VALUES(20,46.124,2.002,800.00,3);
INSERT INTO animal VALUES(501,'Simba',190.5,TO_DATE('2018-06-12','YYYY-MM-DD'),'Carnivore',NULL,10,NULL,1);
INSERT INTO animal VALUES(502,'Nala',150.0,TO_DATE('2019-03-20','YYYY-MM-DD'),'Carnivore',NULL,10,NULL,1);
INSERT INTO animal VALUES(503,'Bébé Tigre',50.0,TO_DATE('2025-05-01','YYYY-MM-DD'),'Carnivore',NULL,10,NULL,1);
INSERT INTO personnel VALUES('user8','Lefebvre','Marc','$2y$10$KVL8zDO4l2fKdi1nuN/G6OndnyW9ULoupyp3sbQCUBWrfH38IDF5i',TO_DATE('2015-01-01','YYYY-MM-DD'),5000,'Dirigeant',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user7','Girard','Thomas','$2y$10$/fnfMQLyL39fgstGUo9gxevDmEbWgE2HnTdzCKd62wGPB9weGRZs6',TO_DATE('2018-03-12','YYYY-MM-DD'),3200,'Gestionnaire',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user6','Petit','Julie','$2y$10$L7EKFcSCOwdoxanKrrYt1eDK8Bhz0W.x98FN1bs8nTTfRwW3ByRB6',TO_DATE('2020-05-20','YYYY-MM-DD'),2800,'Comptable',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user1','Dubois','Alice','$2y$10$sQ5.Eot2ouSpmPuJ.3jEO.2v0cjPPyH2ZGfy.Xr3DrPMPGbkIeice',TO_DATE('2020-01-15','YYYY-MM-DD'),3500,'Chef Soigneur',NULL,NULL,1);
INSERT INTO personnel VALUES('user2','Martin','Bob','$2y$10$eAt91QjtHSuoTPK0dgbdAeWCXTMLjnY52vYcWdrKTXs1EJU8MF6AK',TO_DATE('2022-05-10','YYYY-MM-DD'),2100,'Soigneur','user1',NULL,1);
INSERT INTO personnel VALUES('user5','Moretti','Lucas','$2y$10$Ph3WLbhx0SWTJA3AGmTgROncewQ5904yJd1MNNe1aXQ/FZZ1rVnZa',TO_DATE('2023-11-01','YYYY-MM-DD'),1850,'Personnel Entretien',NULL,NULL,1);
INSERT INTO personnel VALUES('user4','Leroy','Sophie','$2y$10$2xx0QZRLL/FvCWxyUcYPcu9Z8/ZTf7k/UfrhMoWIqTvDDBjzQrR4y',TO_DATE('2021-09-15','YYYY-MM-DD'),2200,'Technicien',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user10','Meyer','David','$2y$10$JSECoPad3dshQdtyWQKSkufhK7MAQQF3/8Mgx1IeSaQV2PetgJXhu',TO_DATE('2019-07-01','YYYY-MM-DD'),4200,'Vétérinaire',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user3','Durant','Charlie','$2y$10$irbAcJBOno.hPGt0Lag8VutwZ3ECVSX0rzmqBCTSYpG7vlcweEhTe',TO_DATE('2023-02-01','YYYY-MM-DD'),2500,'Responsable Boutique',NULL,NULL,NULL);
INSERT INTO personnel VALUES('user9','Roux','Emilie','$2y$10$GD/7FwdVPBSUWiPDYtlkMeqWG97LbY4HU5qwR.SY9o9uQB2kEmudK',TO_DATE('2024-01-10','YYYY-MM-DD'),1750,'Employé Boutique','user3',NULL,NULL);
INSERT INTO boutique VALUES(1,'Boutique Safari',1);
INSERT INTO parrainage VALUES(501,1,'Or','Photo dédicacée et visite VIP');
INSERT INTO soigner VALUES(501,'user2',1,TO_DATE('2026-03-05','YYYY-MM-DD'),1);
INSERT INTO soigner VALUES(502,'user10',2,TO_DATE('2026-03-20','YYYY-MM-DD'),0);
INSERT INTO soigner VALUES(502,'user2',3,TO_DATE('2026-04-01','YYYY-MM-DD'),1);
INSERT INTO soigner VALUES(502,'user2',2,TO_DATE('2026-03-21','YYYY-MM-DD'),1);
INSERT INTO soigner VALUES(502,'user2',1,TO_DATE('2026-03-25','YYYY-MM-DD'),1);
INSERT INTO soigner VALUES(503,'user10',1,TO_DATE('2026-03-26','YYYY-MM-DD'),1);
INSERT INTO soigner VALUES (503,'user2',1,TO_DATE('2026-04-10','YYYY-MM-DD'),0);
INSERT INTO nourrir VALUES(501,'user1',1,TO_DATE('2026-03-09','YYYY-MM-DD'));
INSERT INTO nourrir VALUES(502,'user2',2,TO_DATE('2026-03-08','YYYY-MM-DD'));
INSERT INTO possede VALUES(10,1);
INSERT INTO faite VALUES(20,1);
INSERT INTO faite VALUES(10,2);
INSERT INTO realise VALUES(1,101);
INSERT INTO personnel_technique VALUES('user4',2);
INSERT INTO employe_boutique VALUES('user3',1,1);
INSERT INTO employe_boutique VALUES('user9',1,0);
INSERT INTO ca_journalier VALUES(1,TO_DATE('2026-03-01','YYYY-MM-DD'),1250.50);
INSERT INTO ca_journalier VALUES(1,TO_DATE('2026-03-02','YYYY-MM-DD'),450.00);
INSERT INTO cohabiter VALUES(1,2);
INSERT INTO cohabiter VALUES(1,3);
INSERT INTO cohabiter VALUES(2,1);
INSERT INTO cohabiter VALUES(2,3);
INSERT INTO cohabiter VALUES(3,1);
INSERT INTO cohabiter VALUES(3,2);

COMMIT;
