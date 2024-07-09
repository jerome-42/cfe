<?php

$tables = [
    'cfe_records' => "CREATE TABLE `cfe_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proposal` bigint unsigned DEFAULT NULL,
  `who` bigint unsigned NOT NULL,
  `registerDate` datetime NOT NULL,
  `workDate` date NOT NULL,
  `workType` varchar(255) NOT NULL,
  `beneficiary` varchar(255) NOT NULL,
  `duration` decimal(10,2) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `statusDate` datetime DEFAULT NULL,
  `statusWho` bigint unsigned DEFAULT NULL,
  `rejectedCause` varchar(255) DEFAULT NULL,
  `details` text,
  PRIMARY KEY (`id`),
  INDEX `who`(`who`)
) ENGINE=InnoDB",

    'cfe_proposals' => "CREATE TABLE `cfe_proposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `who` bigint unsigned NOT NULL,
  `registerDate` datetime NOT NULL,
  `priority` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `workType` varchar(255) NOT NULL,
  `beneficiary` varchar(255) NOT NULL,
  `notValidAfterDate` date DEFAULT NULL,
  `details` text,
  `notes` text,
  `canBeClosedByMember` bool,
  `isActive` bool,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB",

    'forms' => "CREATE TABLE `forms`(
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `when` datetime NOT NULL,
  `name` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `ip` varchar(255) NOT NULL,
  `port` int NOT NULL,
  `comment` text NULL,
  `commentBy` int NULL,
  `commentWhen` datetime NULL,
  PRIMARY KEY (`id`),
  INDEX `name`(`name`)
) ENGINE=InnoDB",

    'flarm_logs' => "CREATE TABLE `flarm_logs`(
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `glider` bigint unsigned NOT NULL,
  `when` date NOT NULL,
  `filename` varchar(255) NOT NULL,
  `versionSoft` varchar(255) NOT NULL,
  `versionHard` varchar(255),
  `stealth` tinyint(1),
  `noTrack` tinyint(1),
  `radioId` varchar(255),
  `rangeAvg` int,
  `rangeDetails` varchar(255),
  `rangeBelowMinimum` tinyint(1),
  `aircraftType` varchar(255),
  `flarmResultUrl` varchar(255),
  `who` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`glider`, `when`, `filename`),
  INDEX `glider`(`glider`)
) ENGINE=InnoDB",

    "givavdebtor" => "CREATE TABLE `givavdebtor` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `givavNumber` bigint unsigned NOT NULL,
  `balance` decimal(10, 2) NOT NULL,
  `since` date NOT NULL,
  `lastUpdate` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `givavNumber` (`givavNumber`)
) ENGINE=InnoDB",

    'glider' => "CREATE TABLE `glider` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `immat` varchar(255) NOT NULL,
  `concours` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `aircraftType` varchar(255) NOT NULL,
  `cenExpirationDate` date NULL,
  `aprsExpirationDate` date NULL,
  `osrtUpdateDate` date NULL,
  `visible` tinyint(1) DEFAULT '1' NOT NULL,
  `comment` text NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `immat` (`immat`)
) ENGINE=InnoDB",

    'personnes' => "CREATE TABLE `personnes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `givavNumber` bigint unsigned NOT NULL,
  `isAdmin` tinyint(1) DEFAULT '0',
  `noRevealWhenInDebt tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `givavNumber` (`givavNumber`)
) ENGINE=InnoDB",

    'cfe_todo' => "CREATE TABLE `cfe_todo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `who` bigint unsigned NOT NULL,
  `year` int unsigned NOT NULL,
  `todo` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `who` (`who`)
) ENGINE=InnoDB",

    'settings' => [ "CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `what` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `what` (`what`)
) ENGINE=InnoDB",
                    "INSERT INTO settings(what, value) VALUES ('defaultCFE_TODO_2023', 16*60)",
                    "INSERT INTO settings(what, value) VALUES ('defaultCFE_TODO_2024', 16*60)",
                    "INSERT INTO settings(what, value) VALUES ('defaultCFE_TODO_2025', 16*60)",
                    "INSERT INTO settings(what, value) VALUES ('defaultCFE_TODO_2026', 16*60)",
    ],
];

function checkDatabase($conn, $databaseName) {
    global $tables;
    foreach ($tables as $tableName => $createTableStmts) {
        createTableIfNecessary($conn, $databaseName, $tableName, $createTableStmts);
    }
}

function createOGNUser($conn) {
    $query = "INSERT IGNORE INTO personnes (name, givavNumber) VALUES ('OGN', 0)";
    $sth = $conn->prepare($query);
    $sth->execute();
}

function createTable($conn, $createTableStmts) {
    if (is_array($createTableStmts)) {
        foreach ($createTableStmts as $query) {
            $conn->query($query);
        }
    } else {
        $conn->query($createTableStmts);
    }
}

function createTableIfNecessary($conn, $databaseName, $tableName, $createTableStmts) {
    if (doesTableExists($conn, $databaseName, $tableName) === false)
        createTable($conn, $createTableStmts);
    createOGNUser($conn);
}

function doesTableExists($conn, $databaseName, $table) {
    $query = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA LIKE :databaseName AND TABLE_TYPE LIKE 'BASE TABLE' AND TABLE_NAME = :tableName;";
    $sth = $conn->prepare($query);
    $sth->execute([ ':databaseName' => $databaseName,
                    ':tableName' => $table ]);
    return $sth->rowCount() === 1;
}

