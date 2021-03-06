-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 21, 2022 at 09:28 PM

-- Server version: 5.6.47
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `docsystem_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `clerks`
--

CREATE TABLE `clerks` (
  `school_id` int(4) NOT NULL DEFAULT '1',
  `clerk_first_names` varchar(255) DEFAULT NULL,
  `clerk_surname` varchar(255) DEFAULT NULL,
  `clerk_postal_address` varchar(255) DEFAULT NULL,
  `clerk_telephone_number` varchar(255) DEFAULT NULL,
  `clerk_email_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `clerks`
--

INSERT INTO `clerks` (`school_id`, `clerk_first_names`, `clerk_surname`, `clerk_postal_address`, `clerk_telephone_number`, `clerk_email_address`) VALUES
(1, 'Keith', 'Scott', 'East Gate, Newbiggin', '017681 60032', 'kscottnewbiggin@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `school_id` int(4) NOT NULL DEFAULT '1',
  `document_title` varchar(40) NOT NULL DEFAULT '',
  `version_number` int(4) NOT NULL,
  `document_author` varchar(20) DEFAULT NULL,
  `document_issue_date` date NOT NULL,
  `version_creation_date` date DEFAULT NULL,
  `version_last_review_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`school_id`, `document_title`, `version_number`, `document_author`, `document_issue_date`, `version_creation_date`, `version_last_review_date`) VALUES
(1, 'Charging and Remissions Policy', 1, 'June Saturn', '2017-03-27', '2020-09-29', '2020-09-29'),
(1, 'Equality Scheme', 1, 'Nick Brown', '2019-09-01', '2020-09-29', '2020-09-29'),
(1, 'Complaints Procedure', 1, 'Nick Brown', '2018-01-01', '2020-09-29', '2020-09-29'),
(1, 'Child Protection Policy and Procedures', 1, 'Nick Brown', '2020-09-01', '2020-09-29', '2020-09-16'),
(1, 'Privacy Notice', 1, 'Nick Brown', '2020-09-29', '2020-09-29', '2020-09-29'),
(1, 'Safeguarding Leaflet', 1, 'Nick Brown', '2020-09-29', '2020-09-29', '2020-09-29');

-- --------------------------------------------------------

--
-- Table structure for table `governors`
--

CREATE TABLE `governors` (
  `school_id` int(4) NOT NULL DEFAULT '1',
  `governor_id` int(8) NOT NULL,
  `display_sequence` int(4) DEFAULT '1',
  `governor_first_names` varchar(255) DEFAULT NULL,
  `governor_surname` varchar(255) DEFAULT NULL,
  `governor_type_code` char(4) DEFAULT NULL,
  `governor_role_code` char(4) DEFAULT NULL,
  `governor_responsibilities` varchar(255) DEFAULT NULL,
  `governor_postal_address` varchar(255) DEFAULT NULL,
  `governor_telephone_number` varchar(255) DEFAULT NULL,
  `governor_email_address` varchar(255) DEFAULT NULL,
  `governor_appointment_date` date DEFAULT NULL,
  `governor_term_of_office` int(1) DEFAULT NULL,
  `governor_business_interests` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `governors`
--

INSERT INTO `governors` (`school_id`, `governor_id`, `display_sequence`, `governor_first_names`, `governor_surname`, `governor_type_code`, `governor_role_code`, `governor_responsibilities`, `governor_postal_address`, `governor_telephone_number`, `governor_email_address`, `governor_appointment_date`, `governor_term_of_office`, `governor_business_interests`) VALUES
(1, 1, 0, 'William', 'Holder', '3', '1', '* Finance, Staffing & Premises', 'Newbiggin Green, Newbiggin, Appleby-in-Westmorland CA11 6QQ', '', 'willholder@gmail.com', '2017-06-02', 4, 'Holiday home rental'),
(1, 2, 4, 'Kathy', 'Green', '3', '3', '* Safeguarding Governor<br>* Curriculum & Standards ', 'Newbiggin Grange, Newbiggin, Appleby-in-Westmorland CA11 6QQ', '', 'kathy@starcross.co.uk', '2017-06-02', 4, 'Holiday home rental'),
(1, 4, 1, 'Jo', 'Clayton', '2', '2', '* SEN Governor<br>\r\n* Curriculum & Standards', 'Newbiggin Mill, Newbiggin, Temple Sowerby, Penrith CA10 1TH', '', 'josephinec@btinternet.com', '2020-01-08', 4, ''),
(1, 15, 2, 'Nick', 'Brown', '4', '3', '* Finance, Staffing & Premises', 'c/o Newbigginschool', '', 'nbrown@newbigginschool.cumbria.sch.uk', '2018-09-01', 4, ''),
(1, 16, 3, 'Hayley', 'Robinson', '1', '3', '* Curriculum and Standards', 'c/o Newbiggin School', '', 'hayley.robinson@ksps.cumbria.sch.uk', '2018-09-01', 4, ''),
(1, 7, 5, 'Dennis', 'Taylor', '5', '3', '* Curriculum & Standards', 'Newbiggin Farm, Newbiggin', '', 'dennistaylor407@yahoo.co.uk', '2017-05-23', 4, 'Building construction/Farming');

-- --------------------------------------------------------

--
-- Table structure for table `governor_meeting_attendances`
--

CREATE TABLE `governor_meeting_attendances` (
  `school_id` int(4) NOT NULL DEFAULT '1',
  `meeting_date` date NOT NULL,
  `governor_id` int(4) NOT NULL,
  `governor_present` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `governor_meeting_attendances`
--

INSERT INTO `governor_meeting_attendances` (`school_id`, `meeting_date`, `governor_id`, `governor_present`) VALUES
(1, '2016-11-08', 1, 'Y'),
(1, '2018-10-02', 16, 'Y'),
(1, '2018-03-26', 1, 'Y'),
(1, '2016-03-07', 1, 'Y'),
(1, '2017-05-23', 7, 'N'),
(1, '2016-09-12', 7, 'Y'),
(1, '2018-10-02', 15, 'Y'),
(1, '2020-05-22', 4, 'Y'),
(1, '2016-01-18', 7, 'Y'),
(1, '2017-05-23', 4, 'Y'),
(1, '2016-09-12', 4, 'Y'),
(1, '2018-10-02', 7, 'Y'),
(1, '2020-06-22', 2, 'Y'),
(1, '2016-01-18', 4, 'Y'),
(1, '2017-05-23', 2, 'Y'),
(1, '2016-09-12', 2, 'Y'),
(1, '2018-10-02', 4, 'N'),
(1, '2018-03-15', 7, 'Y'),
(1, '2016-01-18', 2, 'Y'),
(1, '2017-05-23', 1, 'Y'),
(1, '2016-09-12', 1, 'Y'),
(1, '2018-10-02', 2, 'Y'),
(1, '2018-03-15', 4, 'Y'),
(1, '2016-01-18', 1, 'Y'),
(1, '2020-06-22', 16, 'Y'),
(1, '2016-06-13', 7, 'Y'),
(1, '2018-10-02', 1, 'Y'),
(1, '2018-03-15', 2, 'Y'),
(1, '2017-05-02', 7, 'N'),
(1, '2015-11-23', 7, 'Y'),
(1, '2016-06-13', 4, 'Y'),
(1, '2020-05-22', 1, 'Y'),
(1, '2018-03-15', 1, 'Y'),
(1, '2017-05-02', 4, 'Y'),
(1, '2015-11-23', 4, 'Y'),
(1, '2016-06-13', 2, 'Y'),
(1, '2020-05-22', 16, 'Y'),
(1, '2020-05-22', 7, 'Y'),
(1, '2017-05-02', 2, 'Y'),
(1, '2015-11-23', 2, 'Y'),
(1, '2016-06-13', 1, 'Y'),
(1, '2018-04-30', 7, 'Y'),
(1, '2020-06-22', 4, 'Y'),
(1, '2017-05-02', 1, 'Y'),
(1, '2015-11-23', 1, 'Y'),
(1, '2016-04-25', 7, 'Y'),
(1, '2018-04-30', 4, 'N'),
(1, '2017-10-17', 7, 'N'),
(1, '2017-03-07', 7, 'Y'),
(1, '2016-04-25', 4, 'Y'),
(1, '2018-04-30', 2, 'Y'),
(1, '2017-10-17', 4, 'Y'),
(1, '2017-03-07', 4, 'Y'),
(1, '2016-04-25', 2, 'Y'),
(1, '2018-04-30', 1, 'Y'),
(1, '2017-10-17', 2, 'N'),
(1, '2017-03-07', 2, 'Y'),
(1, '2016-04-25', 1, 'Y'),
(1, '2020-05-22', 2, 'Y'),
(1, '2017-10-17', 1, 'Y'),
(1, '2017-03-07', 1, 'Y'),
(1, '2016-03-07', 7, 'Y'),
(1, '2020-06-22', 1, 'Y'),
(1, '2020-05-22', 15, 'Y'),
(1, '2017-01-24', 7, 'Y'),
(1, '2016-03-07', 4, 'N'),
(1, '2018-03-26', 7, 'N'),
(1, '2020-06-22', 7, 'Y'),
(1, '2017-01-24', 4, 'Y'),
(1, '2016-03-07', 2, 'Y'),
(1, '2018-03-26', 4, 'Y'),
(1, '2017-09-19', 7, 'Y'),
(1, '2017-01-24', 2, 'Y'),
(1, '2018-03-26', 2, 'Y'),
(1, '2017-09-19', 4, 'Y'),
(1, '2017-01-24', 1, 'Y'),
(1, '2017-09-19', 2, 'N'),
(1, '2016-11-08', 7, 'Y'),
(1, '2017-09-19', 1, 'Y'),
(1, '2016-11-08', 4, 'Y'),
(1, '2020-06-22', 15, 'Y'),
(1, '2016-11-08', 2, 'Y'),
(1, '2019-04-16', 1, 'Y'),
(1, '2019-04-16', 2, 'Y'),
(1, '2019-04-16', 4, 'Y'),
(1, '2019-04-16', 7, 'Y'),
(1, '2019-04-16', 15, 'Y'),
(1, '2019-04-16', 16, 'Y'),
(1, '2019-07-15', 1, 'Y'),
(1, '2019-07-15', 2, 'Y'),
(1, '2019-07-15', 4, 'Y'),
(1, '2019-07-15', 7, 'Y'),
(1, '2019-07-15', 15, 'Y'),
(1, '2019-07-15', 16, 'Y'),
(1, '2019-11-25', 1, 'Y'),
(1, '2019-11-25', 2, 'Y'),
(1, '2019-11-25', 4, 'Y'),
(1, '2019-11-25', 7, 'Y'),
(1, '2019-11-25', 15, 'Y'),
(1, '2019-11-25', 16, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `governor_roles`
--

CREATE TABLE `governor_roles` (
  `governor_role_code` char(4) NOT NULL,
  `governor_role` varchar(155) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `governor_roles`
--

INSERT INTO `governor_roles` (`governor_role_code`, `governor_role`) VALUES
('1', 'Chair'),
('2', 'Vice-chair'),
('3', 'n/a');

-- --------------------------------------------------------

--
-- Table structure for table `governor_types`
--

CREATE TABLE `governor_types` (
  `governor_type_code` char(4) NOT NULL,
  `governor_type` varchar(155) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `governor_types`
--

INSERT INTO `governor_types` (`governor_type_code`, `governor_type`) VALUES
('1', 'Staff governor'),
('2', 'Local Authority (LA) governor'),
('3', 'Parent governor'),
('4', 'Exec Head teacher'),
('5', 'Co-opted governor'),
('6', 'n/a');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `school_id` int(4) NOT NULL DEFAULT '1',
  `meeting_date` date NOT NULL,
  `meeting_type` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`school_id`, `meeting_date`, `meeting_type`) VALUES
(1, '2016-01-18', 'O'),
(1, '2016-03-07', 'O'),
(1, '2016-04-25', 'O'),
(1, '2016-06-13', 'O'),
(1, '2016-09-12', 'O'),
(1, '2016-11-08', 'O'),
(1, '2017-01-24', 'O'),
(1, '2017-03-07', 'O'),
(1, '2017-05-02', 'X'),
(1, '2017-05-23', 'O'),
(1, '2017-09-19', 'O'),
(1, '2017-10-17', 'O'),
(1, '2018-03-15', 'X'),
(1, '2018-03-26', 'O'),
(1, '2018-04-30', 'O'),
(1, '2018-10-02', 'O'),
(1, '2019-04-16', 'O'),
(1, '2019-07-15', 'O'),
(1, '2019-11-25', 'O'),
(1, '2020-05-22', ''),
(1, '2020-06-22', '');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(4) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `school_name`) VALUES
(1, 'Newbiggin School');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(40) NOT NULL,
  `password` varchar(20) DEFAULT NULL,
  `school_id` int(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `password`, `school_id`) VALUES
('test', 'tst$', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clerks`
--
ALTER TABLE `clerks`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`school_id`,`document_title`,`version_number`);

--
-- Indexes for table `governors`
--
ALTER TABLE `governors`
  ADD PRIMARY KEY (`school_id`,`governor_id`);
  
ALTER TABLE `governors`
  ADD UNIQUE KEY `governor_id` (`governor_id`);

--
-- Indexes for table `governor_meeting_attendances`
--
ALTER TABLE `governor_meeting_attendances`
  ADD PRIMARY KEY (`governor_id`,`meeting_date`,`school_id`),
  ADD KEY `governor_meeting_date` (`meeting_date`,`governor_id`) USING BTREE;

--
-- Indexes for table `governor_roles`
--
ALTER TABLE `governor_roles`
  ADD PRIMARY KEY (`governor_role_code`);

--
-- Indexes for table `governor_types`
--
ALTER TABLE `governor_types`
  ADD PRIMARY KEY (`governor_type_code`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`school_id`,`meeting_date`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `governors`
--
ALTER TABLE `governors`
  MODIFY `governor_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
  

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;  
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
