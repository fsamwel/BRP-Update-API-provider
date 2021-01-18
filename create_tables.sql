CREATE TABLE `upd_wijzigingen` (
  `burgerservicenummer` varchar(9) NOT NULL,
  `datum` date NOT NULL,
  PRIMARY KEY (`burgerservicenummer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `upd_volgindicaties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(128) NOT NULL,
  `burgerservicenummer` varchar(9) NOT NULL,
  `einddatum` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index2` (`user`,`burgerservicenummer`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
