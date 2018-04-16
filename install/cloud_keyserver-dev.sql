SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `blobs`;
CREATE TABLE IF NOT EXISTS `blobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(24) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `share` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2529 ;

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `privkey` text NOT NULL,
  `pubkey` text NOT NULL,
  `privkey_signing` text NOT NULL,
  `pubkey_signing` text NOT NULL,
  `tokenkey` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

DROP TABLE IF EXISTS `cuentas`;
CREATE TABLE IF NOT EXISTS `cuentas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=371 ;

DROP TABLE IF EXISTS `directorios`;
CREATE TABLE IF NOT EXISTS `directorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `account` int(11) NOT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `thumbnail_share` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`,`parent`,`account`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5805 ;

DROP TABLE IF EXISTS `emails_importados`;
CREATE TABLE IF NOT EXISTS `emails_importados` (
  `email` varchar(255) NOT NULL,
  `cuenta` int(11) NOT NULL,
  PRIMARY KEY (`email`,`cuenta`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `errors`;
CREATE TABLE IF NOT EXISTS `errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `keyshares`;
CREATE TABLE IF NOT EXISTS `keyshares` (
  `fileid` int(11) NOT NULL,
  `estado` int(11) NOT NULL,
  `deleted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(255) NOT NULL,
  `size` bigint(11) NOT NULL,
  `directory` int(11) NOT NULL,
  `share` text NOT NULL,
  `account` int(11) NOT NULL,
  PRIMARY KEY (`fileid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `cuando` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msg` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=11341 ;

DROP TABLE IF EXISTS `other_keyshares`;
CREATE TABLE IF NOT EXISTS `other_keyshares` (
  `id` varchar(64) NOT NULL,
  `tipo` varchar(32) NOT NULL,
  `share` text NOT NULL,
  `estado` int(11) NOT NULL DEFAULT '1',
  `cuenta` int(11) NOT NULL,
  PRIMARY KEY (`id`,`tipo`,`cuenta`),
  KEY `cuenta` (`cuenta`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `other_keyshares_subscriptions`;
CREATE TABLE IF NOT EXISTS `other_keyshares_subscriptions` (
  `keysh_id` varchar(64) NOT NULL,
  `cuenta` int(11) NOT NULL,
  `usuario` int(11) NOT NULL,
  PRIMARY KEY (`keysh_id`,`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `permisos`;
CREATE TABLE IF NOT EXISTS `permisos` (
  `id` int(11) NOT NULL,
  `is_directory` tinyint(1) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `write` tinyint(1) NOT NULL DEFAULT '0',
  `exec` tinyint(1) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`,`is_directory`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `permisos_nuevos`;
CREATE TABLE IF NOT EXISTS `permisos_nuevos` (
  `id` int(11) NOT NULL,
  `is_directory` tinyint(1) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `write` tinyint(1) NOT NULL DEFAULT '0',
  `exec` tinyint(1) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `user` varchar(255) NOT NULL,
  PRIMARY KEY (`id`,`is_directory`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `secrets`;
CREATE TABLE IF NOT EXISTS `secrets` (
  `aux_id` int(11) NOT NULL,
  `tipo` varchar(16) NOT NULL DEFAULT 'file',
  `secret` varchar(16) NOT NULL,
  PRIMARY KEY (`aux_id`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(32) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password_hash` varchar(255) NOT NULL,
  `pubkey` text NOT NULL,
  `encrypted_privkey` text NOT NULL,
  `pubkey_signing` text NOT NULL,
  `encrypted_privkey_signing` text NOT NULL,
  `account` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `estado` int(11) NOT NULL DEFAULT '1',
  `almacenado` bigint(11) NOT NULL DEFAULT '0',
  `subido` bigint(11) NOT NULL DEFAULT '0',
  `bajado` bigint(11) NOT NULL DEFAULT '0',
  `email_verificado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=456 ;
