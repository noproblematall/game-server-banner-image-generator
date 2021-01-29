-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.1.38-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             11.0.0.5919
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for game
CREATE DATABASE IF NOT EXISTS `game` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `game`;

-- Dumping structure for table game.info
CREATE TABLE IF NOT EXISTS `info` (
  `id` int(10) unsigned NOT NULL,
  `servername` varchar(50) DEFAULT NULL,
  `game` varchar(50) DEFAULT NULL,
  `severip` varchar(50) DEFAULT NULL,
  `severport` varchar(50) DEFAULT NULL,
  `players` int(10) unsigned NOT NULL,
  `country` varchar(50) DEFAULT NULL,
  `map` varchar(50) DEFAULT NULL,
  `rank` int(11) NOT NULL,
  `status` bit(1) NOT NULL DEFAULT b'1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table game.info: ~1 rows (approximately)
DELETE FROM `info`;
/*!40000 ALTER TABLE `info` DISABLE KEYS */;
INSERT INTO `info` (`id`, `servername`, `game`, `severip`, `severport`, `players`, `country`, `map`, `rank`, `status`) VALUES
	(0, 'BRAZUKAS SERVER', 'ARMA 3', '179.191.208.85', '2302', 25, 'br', 'stratis', 739, b'0');
/*!40000 ALTER TABLE `info` ENABLE KEYS */;

-- Dumping structure for table game.realtimedata
CREATE TABLE IF NOT EXISTS `realtimedata` (
  `id` int(10) unsigned NOT NULL,
  `number` int(10) unsigned DEFAULT NULL,
  `time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table game.realtimedata: ~7 rows (approximately)
DELETE FROM `realtimedata`;
/*!40000 ALTER TABLE `realtimedata` DISABLE KEYS */;
INSERT INTO `realtimedata` (`id`, `number`, `time`) VALUES
	(0, 1, '2020-07-01 21:52:15'),
	(1, 3, '2020-07-02 04:52:39'),
	(2, 5, '2020-07-02 05:53:12'),
	(3, 8, '2020-07-02 05:54:15'),
	(4, 10, '2020-07-02 05:54:23'),
	(5, 20, '2020-07-02 05:54:36'),
	(6, 10, '2020-07-02 05:54:41');
/*!40000 ALTER TABLE `realtimedata` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
