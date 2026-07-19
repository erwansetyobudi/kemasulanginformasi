<?php
/**
 * Plugin Name: SILANPUSBAYA
 * Plugin URI: https://github.com/erwansetyobudi/kemasulanginformasi
 * Description: Plugin Sistem Informasi Layanan Perpustakaan (Kemas Ulang Informasi)
 * Version: 1.0.0
 * Author: Erwan Setyo Budi
 * Author URI: https://github.com/erwansetyobudi/
 */

use SLiMS\Plugins;
use SLiMS\DB;

defined('INDEX_AUTH') OR die('Direct access not allowed!');

$plugins = Plugins::getInstance();

// Register menu di admin bibliografi
$plugins->registerMenu('bibliography', 'SILANPUSBAYA', __DIR__ . '/admin/silanpusbaya_admin.inc.php');

// Register halaman OPAC
$plugins->registerMenu('opac', 'silanpusbaya', __DIR__ . '/opac/silanpusbaya.inc.php');

// Register migration
$plugins->register(Plugins::ADMIN_SESSION_AFTER_START, function() {
    $db = DB::getInstance();
    try {
        $tableExists = $db->query("SHOW TABLES LIKE 'silanpusbaya'");
        if ($tableExists->rowCount() == 0) {
            require_once __DIR__ . '/migration/1_CreateSilanpusbayaTables.php';
            $migration = new CreateSilanpusbayaTables();
            $migration->up();
        }
    } catch (Exception $e) {
        // Error handling
    }
});