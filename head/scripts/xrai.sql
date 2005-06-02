# phpMyAdmin SQL Dump
# version 2.5.6
# http://www.phpmyadmin.net
#
# Serveur: localhost
# G���le : Mercredi 30 Juin 2004 �14:31
# Version du serveur: 4.0.16
# Version de PHP: 4.3.3
# 
# Base de donn�s: inex
# 

# --------------------------------------------------------

#
# Structure de la table assessments
#

CREATE TABLE assessments (
  id_pool smallint(4) NOT NULL default '0',
  in_pool enum('Y','N') NOT NULL default 'N',
  inferred enum('Y','N') NOT NULL default 'Y',
  inconsistant enum('Y','N') NOT NULL default 'N',
  xid int(11) NOT NULL default '0',
  assessment enum('U','00','11','12','13','21','22','23','31','32','33') NOT NULL default 'U',
  PRIMARY KEY  (id_pool,xid),
  KEY infered (inferred),
  KEY assessment (assessment),
  KEY the_pool (id_pool) 
) TYPE=BerkeleyDB;

# --------------------------------------------------------

#
# Structure de la table keywords
#

CREATE TABLE keywords (
  id_pool smallint(3) unsigned NOT NULL default '0',
  color varchar(6) NOT NULL default '',
  keywords text NOT NULL,
  mode enum('color','background','border') NOT NULL default 'color',
  PRIMARY KEY  (id_pool,color,mode)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Structure de la table pools
#

CREATE TABLE pools (
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

CREATE TABLE topics (
  id varchar(5) NOT NULL default '',
  definition text NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;


# --------------------------------------------------------

#
# Structure de la table files
#

CREATE TABLE files (
  name varchar(70) NOT NULL default '',
  parent varchar(70) NOT NULL default '',
  `type` enum('xrai','xml') NOT NULL,
  xsl varchar(70) NOT NULL,
  title text NOT NULL default '',
  xid int(11) NOT NULL default '0',
  post int(11) NOT NULL default '0',
  PRIMARY KEY  (name),
  KEY parent (parent),
  KEY name (name,xid,post),
  KEY xid (xid),
  KEY post (post), 
  KEY type (type)  
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Structure de la table map
#

CREATE TABLE map (
  tag varchar(15) NOT NULL default '',
  rank smallint(6) NOT NULL default '0',
  xid int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  children_count smallint(6) NOT NULL default '0',
  post int(11) NOT NULL default '0',
  path int(11) default NULL,
  PRIMARY KEY  (xid),
  KEY parent (parent),
  KEY rank (rank),
  KEY tag (tag),
  KEY post (post),
  KEY path (path)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Structure de la table paths
#

CREATE TABLE paths (
  id int(11) NOT NULL auto_increment,
  path varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY path (path)
) TYPE=MyISAM;

#
# Structure de la table statistics
#

CREATE TABLE `statistics` (
`id_pool` SMALLINT NOT NULL ,
`client_time` DATETIME NOT NULL ,
`server_time` DATETIME NOT NULL ,
`xid` INT NOT NULL ,
`assessment` ENUM( 'U', '00', '11', '12', '13', '21', '22', '23', '31', '32', '33' ) NOT NULL ,
PRIMARY KEY ( `id_pool` , `server_time` , `xid` )
);

