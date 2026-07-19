<?php
/*
 * File: helper.php
 * Created on Sun Jul 19 2026
 * Last Updated: Sun Jul 19 2026 10:14:56 AM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

/**
 * Helper functions for SILANPUSBAYA plugin
 */

if (!function_exists('getProdiList')) {
    function getProdiList($dbs) {
        $prodi = [];
        $query = $dbs->query("
            SELECT p.prodi_id, p.desk_prodi, f.desk_faculty 
            FROM mst_prodi p 
            LEFT JOIN mst_faculty f ON p.faculty_id = f.faculty_id 
            ORDER BY f.desk_faculty, p.desk_prodi
        ");
        while ($row = $query->fetch_assoc()) {
            $prodi[] = $row;
        }
        return $prodi;
    }
}

if (!function_exists('getSilanpusbayaProdi')) {
    function getSilanpusbayaProdi($dbs, $silanpusbaya_id) {
        $prodi_ids = [];
        $silanpusbaya_id = (int)$silanpusbaya_id;
        if ($silanpusbaya_id <= 0) {
            return $prodi_ids;
        }
        $query = $dbs->query("
            SELECT prodi_id FROM silanpusbaya_prodi WHERE silanpusbaya_id = '" . $silanpusbaya_id . "'
        ");
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $prodi_ids[] = $row['prodi_id'];
            }
        }
        return $prodi_ids;
    }
}

if (!function_exists('getSilanpusbayaFiles')) {
    function getSilanpusbayaFiles($dbs, $silanpusbaya_id) {
        $files = [];
        $silanpusbaya_id = (int)$silanpusbaya_id;
        if ($silanpusbaya_id <= 0) {
            return $files;
        }
        $query = $dbs->query("
            SELECT * FROM silanpusbaya_files 
            WHERE silanpusbaya_id = '" . $silanpusbaya_id . "' 
            ORDER BY sort_order ASC, upload_date ASC
        ");
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                $files[] = $row;
            }
        }
        return $files;
    }
}

if (!function_exists('getFileTypeLabel')) {
    function getFileTypeLabel($type) {
        $labels = [
            'image' => __('Gambar'),
            'pdf' => __('PDF')
        ];
        return $labels[$type] ?? $type;
    }
}

if (!function_exists('getFileIcon')) {
    function getFileIcon($type) {
        $icons = [
            'image' => '🖼️',
            'pdf' => '📄'
        ];
        return $icons[$type] ?? '📄';
    }
}

if (!function_exists('incrementSilanpusbayaView')) {
    function incrementSilanpusbayaView($dbs, $id) {
        $id = (int)$id;
        if ($id <= 0) return false;
        $dbs->query("UPDATE silanpusbaya SET view_count = view_count + 1 WHERE id = '{$id}'");
        return true;
    }
}

if (!function_exists('getSilanpusbayaViewCount')) {
    function getSilanpusbayaViewCount($dbs, $id) {
        $id = (int)$id;
        if ($id <= 0) return 0;
        $query = $dbs->query("SELECT view_count FROM silanpusbaya WHERE id = '{$id}'");
        if ($query && $query->num_rows > 0) {
            $data = $query->fetch_assoc();
            return (int)$data['view_count'];
        }
        return 0;
    }
}

if (!function_exists('getGroupedProdi')) {
    function getGroupedProdi($dbs) {
        $grouped = [];
        $faculty_query = $dbs->query("SELECT faculty_id, desk_faculty FROM mst_faculty ORDER BY desk_faculty");
        if ($faculty_query) {
            while ($faculty = $faculty_query->fetch_assoc()) {
                $prodi_query = $dbs->query("
                    SELECT prodi_id, desk_prodi 
                    FROM mst_prodi 
                    WHERE faculty_id = '" . $faculty['faculty_id'] . "' 
                    ORDER BY desk_prodi
                ");
                $prodi_list = [];
                if ($prodi_query) {
                    while ($prodi = $prodi_query->fetch_assoc()) {
                        $prodi_list[] = $prodi;
                    }
                }
                if (!empty($prodi_list)) {
                    $grouped[$faculty['desk_faculty']] = $prodi_list;
                }
            }
        }
        return $grouped;
    }
}