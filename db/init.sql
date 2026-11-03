-- Cleaned for the repository: visitor IPs replaced with 10.x.x.x placeholders,
-- users with no beeps and their pings removed, created_at column added.
-- MySQL dump 10.19  Distrib 10.3.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: orellapager
-- ------------------------------------------------------
-- Server version	10.3.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `orellapager`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `orellapager` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `orellapager`;

--
-- Table structure for table `beeps`
--

DROP TABLE IF EXISTS `beeps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beeps` (
  `bip` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `post` varchar(140) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`bip`),
  KEY `date` (`date`),
  KEY `uid` (`uid`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1850 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beeps`
--

LOCK TABLES `beeps` WRITE;
/*!40000 ALTER TABLE `beeps` DISABLE KEYS */;
INSERT INTO `beeps` VALUES (1,2,'Hello here!','2020-07-16 22:33:39');
INSERT INTO `beeps` VALUES (2,2,'Beep','2020-07-16 22:33:43');
INSERT INTO `beeps` VALUES (3,2,'This is in memory of the pager!','2020-07-16 22:34:39');
INSERT INTO `beeps` VALUES (4,2,'This is in memory of the pager!','2020-07-16 22:39:27');
INSERT INTO `beeps` VALUES (5,2,'This is in memory of the pager!','2020-07-16 22:47:29');
INSERT INTO `beeps` VALUES (6,2,'This is in memory of the pager!','2020-07-16 22:49:34');
INSERT INTO `beeps` VALUES (7,2,'This is in memory of the pager!','2020-07-16 22:52:42');
INSERT INTO `beeps` VALUES (8,2,'hello','2020-07-16 22:59:50');
INSERT INTO `beeps` VALUES (9,3,'lol','2020-07-16 23:53:37');
INSERT INTO `beeps` VALUES (10,3,'hi','2020-07-17 00:14:01');
INSERT INTO `beeps` VALUES (11,3,'hello, World!','2020-07-17 00:46:46');
INSERT INTO `beeps` VALUES (12,8,'Bottom text ','2020-07-17 03:02:36');
INSERT INTO `beeps` VALUES (13,2,'ho','2020-07-17 03:08:09');
INSERT INTO `beeps` VALUES (14,2,'ho','2020-07-17 03:09:04');
INSERT INTO `beeps` VALUES (15,2,'ho','2020-07-17 03:10:53');
INSERT INTO `beeps` VALUES (16,2,'Hello!!!','2020-07-17 03:11:04');
INSERT INTO `beeps` VALUES (17,2,'Hi this is my Android! \r\nAndroid rocks!!\r\n','2020-07-17 04:27:43');
INSERT INTO `beeps` VALUES (18,2,'Android rules','2020-07-17 04:28:08');
INSERT INTO `beeps` VALUES (19,10,'Beep boop nostalgia!','2020-07-17 04:34:59');
INSERT INTO `beeps` VALUES (20,31,'Lost','2020-07-17 16:07:02');
INSERT INTO `beeps` VALUES (21,41,'Why are you at 31 user IDâ€™s ','2020-07-17 18:54:55');
INSERT INTO `beeps` VALUES (22,41,'Rest ','2020-07-17 18:55:01');
INSERT INTO `beeps` VALUES (23,41,'You have 41 unique user IDâ€™s. That makes no sense. Do select * from users and verify that you have 41 different IP addresses. ','2020-07-17 18:56:49');
INSERT INTO `beeps` VALUES (24,2,'hi','2020-07-18 04:59:22');
INSERT INTO `beeps` VALUES (25,2,'hi','2020-07-18 05:02:56');
INSERT INTO `beeps` VALUES (26,2,'hello','2020-07-18 05:47:03');
INSERT INTO `beeps` VALUES (27,2,'hello!','2020-07-18 05:55:43');
INSERT INTO `beeps` VALUES (28,10,'Hello free world!','2020-07-18 06:55:18');
INSERT INTO `beeps` VALUES (29,2,'BEEP BOOP BEEEP ','2020-07-18 06:59:39');
INSERT INTO `beeps` VALUES (30,2,'Beep','2020-07-18 07:08:25');
INSERT INTO `beeps` VALUES (31,2,'Beep','2020-07-18 07:09:19');
INSERT INTO `beeps` VALUES (32,71,'Based','2020-07-18 16:25:59');
INSERT INTO `beeps` VALUES (33,71,'Redpilled','2020-07-18 16:26:15');
INSERT INTO `beeps` VALUES (34,71,'Redpilled','2020-07-18 16:26:18');
INSERT INTO `beeps` VALUES (35,71,'Redpilled','2020-07-18 16:26:21');
INSERT INTO `beeps` VALUES (36,2,'Nostalgia','2020-07-18 19:39:46');
INSERT INTO `beeps` VALUES (37,41,'Ryan has it','2020-07-18 22:15:28');
INSERT INTO `beeps` VALUES (38,82,'Hello','2020-07-19 00:34:32');
INSERT INTO `beeps` VALUES (39,2,'Hi','2020-07-19 03:50:17');
INSERT INTO `beeps` VALUES (40,2,'Hi','2020-07-19 03:50:49');
INSERT INTO `beeps` VALUES (41,2,'test','2020-07-19 04:02:59');
INSERT INTO `beeps` VALUES (42,2,'test audio','2020-07-19 04:06:04');
INSERT INTO `beeps` VALUES (43,2,'jo','2020-07-19 04:07:40');
INSERT INTO `beeps` VALUES (44,2,'jo','2020-07-19 04:07:44');
INSERT INTO `beeps` VALUES (45,2,'test','2020-07-19 04:13:17');
INSERT INTO `beeps` VALUES (46,2,'test','2020-07-19 04:14:57');
INSERT INTO `beeps` VALUES (47,2,'test','2020-07-19 04:15:19');
INSERT INTO `beeps` VALUES (48,41,'Sound ','2020-07-19 18:58:14');
INSERT INTO `beeps` VALUES (49,41,'Sound','2020-07-19 18:58:19');
INSERT INTO `beeps` VALUES (50,41,'Music playing in Spotify app. Does beep pause my music?','2020-07-19 18:59:30');
INSERT INTO `beeps` VALUES (51,41,'It does not','2020-07-19 18:59:34');
INSERT INTO `beeps` VALUES (52,41,'Uwu','2020-07-19 19:52:34');
INSERT INTO `beeps` VALUES (53,2,'test','2020-07-20 01:11:58');
INSERT INTO `beeps` VALUES (54,2,'test','2020-07-20 01:13:15');
INSERT INTO `beeps` VALUES (55,2,'test','2020-07-20 01:14:01');
INSERT INTO `beeps` VALUES (56,10,'Hello? ','2020-07-20 03:35:37');
INSERT INTO `beeps` VALUES (57,2,'hi','2020-07-20 06:22:58');
INSERT INTO `beeps` VALUES (58,2,'Sounds ','2020-07-20 07:20:25');
INSERT INTO `beeps` VALUES (59,2,'Sounds ','2020-07-20 07:32:04');
INSERT INTO `beeps` VALUES (60,2,'Sounds ','2020-07-20 07:33:58');
INSERT INTO `beeps` VALUES (61,2,'Sounds ','2020-07-20 07:34:16');
INSERT INTO `beeps` VALUES (62,2,'Sounds ','2020-07-20 07:37:45');
INSERT INTO `beeps` VALUES (63,2,'It beeps!','2020-07-20 08:01:18');
INSERT INTO `beeps` VALUES (64,2,'It beeps!','2020-07-20 08:01:37');
INSERT INTO `beeps` VALUES (65,2,'Sounds ','2020-07-20 08:04:27');
INSERT INTO `beeps` VALUES (66,10,'Hi','2020-07-20 15:02:19');
INSERT INTO `beeps` VALUES (67,10,'Hi','2020-07-20 15:02:23');
INSERT INTO `beeps` VALUES (68,41,'Tesr','2020-07-21 17:28:07');
INSERT INTO `beeps` VALUES (69,41,'Fffff','2020-07-21 17:28:15');
INSERT INTO `beeps` VALUES (70,41,'No sound on iPhone ','2020-07-21 17:28:21');
INSERT INTO `beeps` VALUES (71,41,'Lol wtf I didnâ€™t know the black button played a ringtone','2020-07-21 17:28:43');
INSERT INTO `beeps` VALUES (72,41,'jfjfjfjfjfjjffj','2020-07-27 06:05:42');
INSERT INTO `beeps` VALUES (73,41,'\" union * from users','2020-07-27 06:05:52');
INSERT INTO `beeps` VALUES (74,41,'\" union * from users \"','2020-07-27 06:06:05');
INSERT INTO `beeps` VALUES (75,41,'\" \r\n','2020-07-27 06:06:16');
INSERT INTO `beeps` VALUES (76,41,'\"','2020-07-27 06:06:25');
INSERT INTO `beeps` VALUES (77,41,'\"\r\n\r\n','2020-07-27 06:06:33');
INSERT INTO `beeps` VALUES (78,41,'\"\r\n\r\n','2020-07-27 06:07:35');
INSERT INTO `beeps` VALUES (79,41,'\" UNION  * FROM USERS );','2020-07-27 06:08:08');
INSERT INTO `beeps` VALUES (80,41,'\");','2020-07-27 06:08:14');
INSERT INTO `beeps` VALUES (81,41,';','2020-07-27 06:08:19');
INSERT INTO `beeps` VALUES (82,41,'\";','2020-07-27 06:08:29');
INSERT INTO `beeps` VALUES (83,41,'\" SELECT * FROM * ;','2020-07-27 06:08:48');
INSERT INTO `beeps` VALUES (84,41,'\" SELECT *;','2020-07-27 06:08:54');
INSERT INTO `beeps` VALUES (85,41,'(\" SELECT * FROM USERS;','2020-07-27 06:09:01');
INSERT INTO `beeps` VALUES (86,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:10');
INSERT INTO `beeps` VALUES (87,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:13');
INSERT INTO `beeps` VALUES (88,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:14');
INSERT INTO `beeps` VALUES (89,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:15');
INSERT INTO `beeps` VALUES (90,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:16');
INSERT INTO `beeps` VALUES (91,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:17');
INSERT INTO `beeps` VALUES (92,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:18');
INSERT INTO `beeps` VALUES (93,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:19');
INSERT INTO `beeps` VALUES (94,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:20');
INSERT INTO `beeps` VALUES (95,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:21');
INSERT INTO `beeps` VALUES (96,41,'\" SELECT * FROM USERS;','2020-07-27 06:09:31');
INSERT INTO `beeps` VALUES (97,41,'\" SELECT * FROM USERS\");','2020-07-27 06:09:34');
INSERT INTO `beeps` VALUES (98,41,'\" ; \"','2020-07-27 06:10:02');
INSERT INTO `beeps` VALUES (99,41,'105 OR 1=1','2020-07-27 06:10:38');
INSERT INTO `beeps` VALUES (100,41,'\" or \"\"=\"','2020-07-27 06:11:13');
INSERT INTO `beeps` VALUES (101,41,'REEEEEEEEEEEEEEEEEEEEEEEEEEe sql injection didnt work','2020-07-27 06:11:24');
INSERT INTO `beeps` VALUES (102,2,'Stop attempting to hack lol at 41\r\n','2020-07-28 03:30:55');
INSERT INTO `beeps` VALUES (103,202,'Stop attempting to hack lol at 41\r\n','2020-07-28 03:30:57');
INSERT INTO `beeps` VALUES (104,2,'hi','2020-07-31 00:18:35');
INSERT INTO `beeps` VALUES (105,347,'hi','2020-07-31 00:18:36');
INSERT INTO `beeps` VALUES (106,2,'repeat','2020-07-31 00:40:04');
INSERT INTO `beeps` VALUES (107,41,'41 is me ','2020-07-31 04:07:40');
INSERT INTO `beeps` VALUES (108,41,'41 is me ','2020-07-31 04:51:06');
INSERT INTO `beeps` VALUES (109,41,'That last one was because I refreshed   Not because of the duplicate tweet glitch ','2020-07-31 04:51:29');
INSERT INTO `beeps` VALUES (110,390,'Cringemax420 PRoxypost','2020-08-02 08:49:31');
INSERT INTO `beeps` VALUES (111,390,'c r i n g e m a x 4 2 0 p r o x y p o s t ','2020-08-02 08:50:01');
INSERT INTO `beeps` VALUES (112,390,'\";','2020-08-02 08:50:11');
INSERT INTO `beeps` VALUES (113,712,'Ð­Ð¢Ðž Ð§Ð¢Ðž? :d','2020-08-20 16:27:52');
INSERT INTO `beeps` VALUES (114,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:34');
INSERT INTO `beeps` VALUES (115,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:38');
INSERT INTO `beeps` VALUES (116,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:41');
INSERT INTO `beeps` VALUES (117,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:43');
INSERT INTO `beeps` VALUES (118,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:45');
INSERT INTO `beeps` VALUES (119,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:48');
INSERT INTO `beeps` VALUES (120,712,'BUY BITCOIN!!!!!!!','2020-08-20 16:29:56');
INSERT INTO `beeps` VALUES (121,775,'Stop attempting to hack lol at 41\r\n','2020-08-25 02:26:37');
INSERT INTO `beeps` VALUES (122,712,'BUY BITCOIN!!!!!!!','2020-08-26 11:31:24');
INSERT INTO `beeps` VALUES (123,2,'why should I buy bitcoin anD from where?\r\n','2020-08-27 01:26:15');
INSERT INTO `beeps` VALUES (124,828,'hi','2020-08-28 04:05:03');
INSERT INTO `beeps` VALUES (125,905,'Hey!!','2020-08-31 15:36:04');
INSERT INTO `beeps` VALUES (126,924,'hello from ipvanish\r\n\r\n','2020-09-01 05:50:20');
INSERT INTO `beeps` VALUES (127,1341,'learn 2 code','2020-09-23 05:00:06');
INSERT INTO `beeps` VALUES (128,1341,'learn 2 code','2020-09-23 05:09:10');
INSERT INTO `beeps` VALUES (129,1341,'n','2020-09-23 05:09:18');
INSERT INTO `beeps` VALUES (130,1841,'hi','2020-10-18 14:58:43');
INSERT INTO `beeps` VALUES (131,2678,'h','2020-11-22 12:49:52');
INSERT INTO `beeps` VALUES (133,4260,'hello','2021-02-09 21:26:57');
INSERT INTO `beeps` VALUES (134,4260,'SELECT * FROM USERS','2021-02-09 21:27:21');
INSERT INTO `beeps` VALUES (135,2,'SELECT * from users;\r\n','2021-02-13 03:45:05');
INSERT INTO `beeps` VALUES (136,5079,'hello from my home!','2021-03-24 01:36:19');
INSERT INTO `beeps` VALUES (137,5438,'Hola David, get lit','2021-04-06 06:39:49');
INSERT INTO `beeps` VALUES (138,4897,'hi','2021-04-23 06:52:03');
INSERT INTO `beeps` VALUES (139,4897,'hi','2021-04-23 06:53:09');
INSERT INTO `beeps` VALUES (140,4897,'hi','2021-04-23 06:53:16');
INSERT INTO `beeps` VALUES (142,9536,'hhh','2021-10-27 12:20:05');
INSERT INTO `beeps` VALUES (143,12122,'Lh','2022-03-13 21:33:13');
INSERT INTO `beeps` VALUES (144,12212,'SÐ¾me Ð¾f us think holding on mÐ°kÐµs us strong, but sometimes it is letting gÐ¾.','2022-03-23 11:32:09');
INSERT INTO `beeps` VALUES (147,8288,'Keep your chin up and Never Give up. you Got this!!!@','2022-04-01 16:25:20');
INSERT INTO `beeps` VALUES (175,12933,'Love is the key to happiness','2022-04-30 04:19:40');
INSERT INTO `beeps` VALUES (443,15940,'ÐŸÑ€Ñ‹Ð²Ñ–Ñ‚Ð°Ð½Ð½Ðµ, Ñ Ñ…Ð°Ñ†ÐµÑž Ð´Ð°Ð²ÐµÐ´Ð°Ñ†Ñ†Ð° Ð’Ð°Ñˆ Ð¿Ñ€Ð°Ð¹Ñ.','2022-10-06 21:04:52');
INSERT INTO `beeps` VALUES (466,16247,'xgVl','2022-10-19 19:29:56');
INSERT INTO `beeps` VALUES (467,16247,'xgVl','2022-10-19 19:30:02');
INSERT INTO `beeps` VALUES (468,16247,'xgVl)\'(),)\",()','2022-10-19 19:30:09');
INSERT INTO `beeps` VALUES (469,16247,'xgVl\'cXjjxv<\'\">iHSexp','2022-10-19 19:30:16');
INSERT INTO `beeps` VALUES (470,16247,'grwC','2022-10-20 14:08:14');
INSERT INTO `beeps` VALUES (471,16247,'grwC,(.\",),)\'(','2022-10-20 14:08:19');
INSERT INTO `beeps` VALUES (472,16247,'grwC\'qTvvSY<\'\">SUEYrf','2022-10-20 14:08:25');
INSERT INTO `beeps` VALUES (486,16378,'Christ is king','2022-10-26 05:23:04');
INSERT INTO `beeps` VALUES (487,15873,'AI','2022-10-26 05:25:23');
INSERT INTO `beeps` VALUES (584,15873,'Benis :DDDDD\r\n','2023-01-12 23:11:43');
INSERT INTO `beeps` VALUES (593,15873,'beep','2023-01-14 06:51:25');
INSERT INTO `beeps` VALUES (594,15873,'beep','2023-01-14 06:51:47');
INSERT INTO `beeps` VALUES (598,15873,'hi','2023-01-14 07:05:55');
INSERT INTO `beeps` VALUES (603,15873,'fr','2023-01-16 04:47:39');
INSERT INTO `beeps` VALUES (604,15873,'Deutschland','2023-01-16 04:48:44');
INSERT INTO `beeps` VALUES (605,16378,'I hope you like the book','2023-01-16 04:54:18');
INSERT INTO `beeps` VALUES (606,17974,'Christ is king','2023-01-16 04:59:05');
INSERT INTO `beeps` VALUES (607,17974,'<script>alert(â€œhiâ€)</script>','2023-01-16 05:00:04');
INSERT INTO `beeps` VALUES (609,15873,'Argentina','2023-01-16 05:17:00');
INSERT INTO `beeps` VALUES (610,15873,'ed','2023-01-16 05:38:33');
INSERT INTO `beeps` VALUES (611,15873,'ed','2023-01-16 05:40:18');
INSERT INTO `beeps` VALUES (612,15873,'dec','2023-01-16 05:40:48');
INSERT INTO `beeps` VALUES (613,15873,'Ehd','2023-01-16 05:45:11');
INSERT INTO `beeps` VALUES (614,15873,'Ole ole','2023-01-16 05:45:37');
INSERT INTO `beeps` VALUES (615,17987,'Life','2023-01-16 15:49:19');
INSERT INTO `beeps` VALUES (616,15873,'lol','2023-01-18 08:23:49');
INSERT INTO `beeps` VALUES (617,15873,'We love beeps ','2023-01-18 08:25:32');
INSERT INTO `beeps` VALUES (618,15873,'beep','2023-01-18 08:25:53');
INSERT INTO `beeps` VALUES (619,15873,'We love beeps','2023-01-18 08:26:17');
INSERT INTO `beeps` VALUES (620,15873,'BEEPING ALL THE WAY','2023-01-18 08:26:44');
INSERT INTO `beeps` VALUES (621,15873,'BEEP ALL THE WAY','2023-01-18 08:29:36');
INSERT INTO `beeps` VALUES (622,15873,'Hello','2023-01-18 08:34:10');
INSERT INTO `beeps` VALUES (623,15873,'Beep on iphone','2023-01-18 08:34:52');
INSERT INTO `beeps` VALUES (624,15873,'beep on thinkpad','2023-01-18 08:35:08');
INSERT INTO `beeps` VALUES (625,15873,'Linux Mint is awesome','2023-01-19 03:25:56');
INSERT INTO `beeps` VALUES (626,15873,'Hi ','2023-01-25 02:10:30');
INSERT INTO `beeps` VALUES (627,18600,'Greetings partner','2023-02-14 15:37:04');
INSERT INTO `beeps` VALUES (672,24213,' Spectacular island  \r\n','2023-11-10 18:25:56');
INSERT INTO `beeps` VALUES (676,24570,' \r\nGreat post.','2023-11-11 01:27:45');
INSERT INTO `beeps` VALUES (685,24698,'sorry you got hit with the cialis ads big dog','2023-11-15 03:42:43');
INSERT INTO `beeps` VALUES (686,20593,'hello with REINforced Captcha','2023-11-16 07:55:58');
INSERT INTO `beeps` VALUES (687,20593,'hello','2023-11-16 07:57:12');
INSERT INTO `beeps` VALUES (688,20593,'hello AMERICA ','2023-11-16 08:02:46');
INSERT INTO `beeps` VALUES (689,20593,'goodnight!','2023-11-16 09:25:08');
INSERT INTO `beeps` VALUES (690,24740,'Good morning!','2023-11-16 15:01:00');
INSERT INTO `beeps` VALUES (691,24698,'a','2023-11-16 19:44:51');
INSERT INTO `beeps` VALUES (692,24698,'reeeeeeeeee','2023-11-16 19:55:35');
INSERT INTO `beeps` VALUES (694,25351,'Posted from work VPN','2023-12-06 20:05:19');
INSERT INTO `beeps` VALUES (912,27130,'FROM ADMIN:\r\nAny spam will be deleted','2024-03-06 15:56:46');
INSERT INTO `beeps` VALUES (951,28411,'this is a spam message roflmao','2024-03-20 19:01:36');
INSERT INTO `beeps` VALUES (952,28411,'\\<a onmouseover=â€œalert(document.cookie)â€\\> lol</a\\>','2024-03-20 19:04:21');
INSERT INTO `beeps` VALUES (955,28412,'<body background=â€œjavascript:alert(â€˜pwnedâ€™)â€>','2024-03-20 19:11:31');
INSERT INTO `beeps` VALUES (956,28415,'Foobar','2024-03-20 19:16:01');
INSERT INTO `beeps` VALUES (957,28415,'Lolz Iâ€™m back! \r\n\\<h1\\> I am king of this website\\</h1\\>','2024-03-20 19:17:09');
INSERT INTO `beeps` VALUES (960,28415,'I am shadowbanned','2024-03-20 19:30:38');
INSERT INTO `beeps` VALUES (962,27130,'	Lolz Iâ€™m back! \\<h1\\> I am king of this website\\</h1\\>','2024-03-22 20:47:10');
INSERT INTO `beeps` VALUES (963,27130,'I will get you @ at 28412, 28415','2024-03-22 20:53:07');
INSERT INTO `beeps` VALUES (1733,38147,'Good news for all us','2025-02-01 14:32:51');
INSERT INTO `beeps` VALUES (1742,39282,'Dich vu gai goi, mai dam. Lien he 0962117278','2025-02-25 02:28:25');
INSERT INTO `beeps` VALUES (1773,43096,'hi david you left your  old ucsc id at the mchenry library on campus please come to the circulation desk to retrieve it','2025-05-29 03:17:20');
/*!40000 ALTER TABLE `beeps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pings`
--

DROP TABLE IF EXISTS `pings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pings` (
  `uid` int(11) NOT NULL,
  `beeper` int(11) NOT NULL,
  PRIMARY KEY (`uid`,`beeper`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pings`
--

LOCK TABLES `pings` WRITE;
/*!40000 ALTER TABLE `pings` DISABLE KEYS */;
INSERT INTO `pings` VALUES (2,2);
INSERT INTO `pings` VALUES (2,1341);
INSERT INTO `pings` VALUES (3,2);
INSERT INTO `pings` VALUES (3,3);
INSERT INTO `pings` VALUES (8,2);
INSERT INTO `pings` VALUES (10,2);
INSERT INTO `pings` VALUES (41,41);
INSERT INTO `pings` VALUES (202,3);
INSERT INTO `pings` VALUES (924,2);
INSERT INTO `pings` VALUES (1841,3);
INSERT INTO `pings` VALUES (1841,1341);
INSERT INTO `pings` VALUES (4897,2);
INSERT INTO `pings` VALUES (8288,8288);
INSERT INTO `pings` VALUES (9536,9536);
INSERT INTO `pings` VALUES (12212,31);
INSERT INTO `pings` VALUES (15873,16378);
INSERT INTO `pings` VALUES (15873,17974);
INSERT INTO `pings` VALUES (16247,712);
/*!40000 ALTER TABLE `pings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=61534 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'10.0.0.3');
INSERT INTO `users` VALUES (12122,'10.0.47.90');
INSERT INTO `users` VALUES (24698,'10.0.96.122');
INSERT INTO `users` VALUES (12933,'10.0.50.133');
INSERT INTO `users` VALUES (12212,'10.0.47.180');
INSERT INTO `users` VALUES (39282,'10.0.153.114');
INSERT INTO `users` VALUES (43096,'10.0.168.88');
INSERT INTO `users` VALUES (41,'10.0.0.41');
INSERT INTO `users` VALUES (4897,'10.0.19.33');
INSERT INTO `users` VALUES (8288,'10.0.32.96');
INSERT INTO `users` VALUES (25351,'10.0.99.7');
INSERT INTO `users` VALUES (202,'10.0.0.202');
INSERT INTO `users` VALUES (28415,'10.0.110.255');
INSERT INTO `users` VALUES (28411,'10.0.110.251');
INSERT INTO `users` VALUES (28412,'10.0.110.252');
INSERT INTO `users` VALUES (1841,'10.0.7.49');
INSERT INTO `users` VALUES (31,'10.0.0.31');
INSERT INTO `users` VALUES (17974,'10.0.70.54');
INSERT INTO `users` VALUES (1341,'10.0.5.61');
INSERT INTO `users` VALUES (390,'10.0.1.134');
INSERT INTO `users` VALUES (5079,'10.0.19.215');
INSERT INTO `users` VALUES (24740,'10.0.96.164');
INSERT INTO `users` VALUES (924,'10.0.3.156');
INSERT INTO `users` VALUES (9536,'10.0.37.64');
INSERT INTO `users` VALUES (712,'10.0.2.200');
INSERT INTO `users` VALUES (17987,'10.0.70.67');
INSERT INTO `users` VALUES (16378,'10.0.63.250');
INSERT INTO `users` VALUES (10,'10.0.0.10');
INSERT INTO `users` VALUES (775,'10.0.3.7');
INSERT INTO `users` VALUES (24570,'10.0.95.250');
INSERT INTO `users` VALUES (71,'10.0.0.71');
INSERT INTO `users` VALUES (15940,'10.0.62.68');
INSERT INTO `users` VALUES (828,'10.0.3.60');
INSERT INTO `users` VALUES (38147,'10.0.149.3');
INSERT INTO `users` VALUES (347,'10.0.1.91');
INSERT INTO `users` VALUES (82,'10.0.0.82');
INSERT INTO `users` VALUES (2678,'10.0.10.118');
INSERT INTO `users` VALUES (5438,'10.0.21.62');
INSERT INTO `users` VALUES (4260,'10.0.16.164');
INSERT INTO `users` VALUES (2,'10.0.0.2');
INSERT INTO `users` VALUES (18600,'10.0.72.168');
INSERT INTO `users` VALUES (15873,'10.0.62.1');
INSERT INTO `users` VALUES (20593,'10.0.80.113');
INSERT INTO `users` VALUES (8,'10.0.0.8');
INSERT INTO `users` VALUES (16247,'10.0.63.119');
INSERT INTO `users` VALUES (24213,'10.0.94.149');
INSERT INTO `users` VALUES (905,'10.0.3.137');
INSERT INTO `users` VALUES (27130,'10.0.105.250');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Retention: when each IP was first recorded. Rows older than RETENTION_DAYS
-- with no beeps are removed daily by docker/purge-stale-ips.php.
-- Imported rows get the import time, so they start a fresh 30-day clock.
--
ALTER TABLE `users`
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  ADD KEY `created_at` (`created_at`);

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-30  3:50:08
