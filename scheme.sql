CREATE TABLE cFotografia2010__elems
(
	elem_id		int unsigned auto_increment primary key,
	titulo		varchar(128) not null,
	imagen		varchar(64) not null,
	votos		int unsigned not null default 0
)
CHARSET=binary;

CREATE TABLE cFotografia2010__record
(
	record_id	int unsigned auto_increment primary key,
	voter_id	varchar(32) not null,
	elem_id		int unsigned,
	
	foreign key (elem_id) references cFotografia2010__elems(elem_id)
	on update cascade on delete cascade
)
CHARSET=binary;

INSERT INTO cFotografia2010__elems(imagen,titulo) VALUES
("http://localhost/survey/th_img/001.jpg", "Un día nublado.");

INSERT INTO cFotografia2010__elems(imagen,titulo) VALUES
("http://localhost/survey/th_img/002.jpg", "Una pequeña flor en medio de la nada.");

INSERT INTO cFotografia2010__elems(imagen,titulo) VALUES
("http://localhost/survey/th_img/003.jpg", "Barco a la deriva de la montaña.");
