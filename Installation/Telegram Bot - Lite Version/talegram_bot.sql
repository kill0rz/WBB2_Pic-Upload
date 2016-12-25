DROP TABLE IF EXISTS `tb_pictures_queue`;
CREATE TABLE `tb_pictures_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(500) NOT NULL,
  `location` varchar(500) NOT NULL,
  `threadname` varchar(500) DEFAULT NULL,
  `current` tinyint(1) NOT NULL DEFAULT '0',
  `telegramfileid` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `tb_set_topic`;
CREATE TABLE `tb_set_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicname` varchar(999) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `tb_set_topic` (`id`, `topicname`) VALUES
(1,	'default');