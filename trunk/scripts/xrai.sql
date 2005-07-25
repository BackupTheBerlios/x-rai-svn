#
# SQL file for X-Rai
# (c) 2005 B. Piwowarski
#


# --------------------------------------------------------

#
# Keywords table: contain information on selected keywords
#

CREATE TABLE IF NOT EXISTS  keywords (
  id_pool smallint(3) unsigned NOT NULL default '0',
  color varchar(6) NOT NULL default '',
  keywords text NOT NULL,
  mode enum('color','background','border') NOT NULL default 'color',
  PRIMARY KEY  (id_pool,color,mode)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# A pool is a given topic judged by a given assessor
#

CREATE TABLE IF NOT EXISTS pools (
  id_pool smallint(20) NOT NULL auto_increment,
  id_topic int(11) NOT NULL default '0',
  type enum('CO','CAS') default NULL,
  login varchar(20) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  state enum('demo','official') NOT NULL default 'official',
  enabled enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (id_pool),
  KEY login (login),
  KEY status (state),
  KEY enabled (enabled)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Structure de la table topics
#

CREATE TABLE IF NOT EXISTS  topics (
  id varchar(5) NOT NULL default '',
  definition text NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;


# --------------------------------------------------------

#
# Table "Collections"
#

CREATE TABLE IF NOT EXISTS  collections (
   id varchar(10) NOT NULL PRIMARY KEY,
   title tinytext
);


# --------------------------------------------------------

#
# Table "ToAssess"
#

CREATE TABLE IF NOT EXISTS toassess (
   pool int,
   collection varchar(10),
   file varchar(70),
   done bool,
   CONSTRAINT ToAssessPK PRIMARY KEY (pool,collection,file),
   KEY doneIndex (done)
);

