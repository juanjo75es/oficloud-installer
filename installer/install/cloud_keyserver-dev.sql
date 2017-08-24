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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

DROP TABLE IF EXISTS `directorios`;
CREATE TABLE IF NOT EXISTS `directorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `account` int(11) NOT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`,`parent`,`account`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1876 ;

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;