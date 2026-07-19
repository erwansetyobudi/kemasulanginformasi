<?php
/*
 * File: 1_CreateSilanpusbayaTables.php
 * Created on Sun Jul 19 2026
 * Last Updated: Sun Jul 19 2026 10:14:22 AM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

/**
 * Migration - Create SILANPUSBAYA tables
 */

use SLiMS\Migration\Migration;
use SLiMS\DB;

class CreateSilanpusbayaTables extends Migration
{
    function up() {
        $db = DB::getInstance();
        
        // Tabel utama silanpusbaya
        $db->query("
            CREATE TABLE IF NOT EXISTS `silanpusbaya` (
                `id` int NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `subject` varchar(255),
                `description` text,
                `view_count` int NOT NULL DEFAULT 0,
                `upload_date` datetime NOT NULL,
                `last_update` datetime DEFAULT NULL,
                `uid` int DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabel relasi silanpusbaya_prodi
        $db->query("
            CREATE TABLE IF NOT EXISTS `silanpusbaya_prodi` (
                `silanpusbaya_id` int NOT NULL,
                `prodi_id` int NOT NULL,
                PRIMARY KEY (`silanpusbaya_id`, `prodi_id`),
                KEY `prodi_id` (`prodi_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabel file silanpusbaya (multiple files)
        $db->query("
            CREATE TABLE IF NOT EXISTS `silanpusbaya_files` (
                `file_id` int NOT NULL AUTO_INCREMENT,
                `silanpusbaya_id` int NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_original` varchar(255) NOT NULL,
                `file_type` enum('image','pdf') NOT NULL,
                `file_size` int DEFAULT 0,
                `description` text,
                `upload_date` datetime NOT NULL,
                `sort_order` int DEFAULT 0,
                PRIMARY KEY (`file_id`),
                KEY `silanpusbaya_id` (`silanpusbaya_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    function down() {
        $db = DB::getInstance();
        $db->query("DROP TABLE IF EXISTS `silanpusbaya_files`");
        $db->query("DROP TABLE IF EXISTS `silanpusbaya_prodi`");
        $db->query("DROP TABLE IF EXISTS `silanpusbaya`");
    }
}