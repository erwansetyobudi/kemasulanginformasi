<?php
/*
 * File: silanpusbaya.inc.php
 * Created on Sun Jul 19 2026
 * Last Updated: Sun Jul 19 2026 10:14:37 AM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

/**
 * OPAC Page - SILANPUSBAYA Display
 * Menampilkan layanan kemas ulang informasi dengan carousel gambar
 */

use SLiMS\DB;

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// Pastikan header yang benar
header("Content-Type: text/html; charset=UTF-8");

do_checkIP('opac');
do_checkIP('opac-member');

$db = DB::getInstance();

// Load helper
require_once __DIR__ . '/../helper.php';

// ============================================================
// HANDLE AJAX SEARCH
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && !isset($_GET['action'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $prodi_id = isset($_GET['prodi_id']) ? (int)$_GET['prodi_id'] : 0;
    
    $sql = "SELECT DISTINCT s.* 
            FROM silanpusbaya s
            LEFT JOIN silanpusbaya_prodi sp ON s.id = sp.silanpusbaya_id
            WHERE 1=1";
    $params = [];

    if ($prodi_id > 0) {
        $sql .= " AND sp.prodi_id = ?";
        $params[] = $prodi_id;
    }

    if (!empty($search)) {
        $sql .= " AND (s.title LIKE ? OR s.subject LIKE ? OR s.description LIKE ?)";
        $like = '%' . $search . '%';
        $params = array_merge($params, [$like, $like, $like]);
    }

    $sql .= " ORDER BY s.title ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    ob_start();
    
    if (empty($items)) {
        ?>
        <div style="text-align:center; padding:60px; background:white; border-radius:12px; color:#6c757d; font-size:18px;">
            <div style="font-size:64px;">📚</div>
            <p style="margin-top:20px;"><?php echo __('Belum ada data yang tersedia atau tidak ditemukan'); ?></p>
            <?php if (!empty($search)): ?>
                <p style="font-size:14px; color:#95a5a6;"><?php echo __('Coba dengan kata kunci lain'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    } else {
        ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
            <?php foreach ($items as $item): 
                $files = getSilanpusbayaFiles($dbs, $item['id']);
                $thumbnail = null;
                foreach ($files as $f) {
                    if ($f['file_type'] == 'image') {
                        $thumbnail = $f;
                        break;
                    }
                }
            ?>
                <div class="silan-card" style="background:white; border-radius:12px; padding:20px; border:1px solid #e9ecef; transition:all 0.3s; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    <?php if ($thumbnail): ?>
                        <div style="text-align:center; margin-bottom:15px; background:#f0f2f5; border-radius:8px; padding:10px; cursor:pointer;" 
                             onclick="openCarousel(<?php echo $item['id']; ?>, <?php echo $thumbnail['file_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['title'])); ?>')">
                            <img src="<?php echo SWB; ?>files/silanpusbaya/<?php echo $thumbnail['file_name']; ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 style="width:100%; max-height:180px; object-fit:contain; border-radius:4px;"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'padding:30px; color:#999; text-align:center;\'>🖼️ <?php echo htmlspecialchars($thumbnail['file_original']); ?></div>';">
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; background:#f8f9fa; border-radius:8px; margin-bottom:15px; color:#999;">
                            📄 <?php echo __('No Image'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="margin:0 0 8px 0; font-size:17px; color:#1a1a2e;">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </h3>
                    
                    <?php if (!empty($item['subject'])): ?>
                        <div style="font-size:13px; color:#6c757d; margin-bottom:5px;">
                            <strong><?php echo __('Subjek'); ?>:</strong> <?php echo htmlspecialchars($item['subject']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['description'])): ?>
                        <div style="font-size:14px; color:#495057; line-height:1.6; margin:10px 0;">
                            <?php 
                            $desc = strip_tags($item['description']);
                            if (strlen($desc) > 100) {
                                echo nl2br(htmlspecialchars(substr($desc, 0, 100))) . '...';
                                echo ' <a href="javascript:void(0)" onclick="toggleDescription(this)" style="color:#0d6efd; text-decoration:none; font-weight:500;">' . __('selengkapnya') . '</a>';
                                echo '<div class="desc-full" style="display:none; margin-top:5px;">' . nl2br(htmlspecialchars($desc)) . '</div>';
                            } else {
                                echo nl2br(htmlspecialchars($desc));
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($files)): ?>
                        <div style="margin-top:15px; border-top:1px solid #e9ecef; padding-top:12px;">
                            <div style="font-size:13px; font-weight:600; color:#495057; margin-bottom:8px;">
                                📎 <?php echo __('File Terkait'); ?> (<?php echo count($files); ?>)
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                <?php foreach ($files as $file): 
                                    $file_url = SWB . 'files/silanpusbaya/' . $file['file_name'];
                                    if ($file['file_type'] == 'image'):
                                ?>
                                    <a href="javascript:void(0)" onclick="openCarousel(<?php echo $item['id']; ?>, <?php echo $file['file_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['title'])); ?>')" 
                                       style="display:inline-block; padding:4px 10px; background:#e8f0fe; color:#1a73e8; border-radius:6px; text-decoration:none; font-size:11px; transition:all 0.2s;">
                                        🖼️ <?php echo htmlspecialchars(substr($file['file_original'], 0, 15)) . (strlen($file['file_original']) > 15 ? '...' : ''); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $file_url; ?>" target="_blank" 
                                       style="display:inline-block; padding:4px 10px; background:#fce8e6; color:#d93025; border-radius:6px; text-decoration:none; font-size:11px; transition:all 0.2s;">
                                        📄 <?php echo htmlspecialchars(substr($file['file_original'], 0, 15)) . (strlen($file['file_original']) > 15 ? '...' : ''); ?>
                                    </a>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; background:#f8f9fa; color:#6c757d;">
                            👁️ <?php echo number_format($item['view_count']); ?>
                        </span>
                        <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; background:#e9ecef; color:#6c757d;">
                            <?php echo date('d M Y', strtotime($item['upload_date'])); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    $html = ob_get_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => count($items)
    ]);
    exit();
}

// ============================================================
// HANDLE GET IMAGE DATA FOR CAROUSEL
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['action']) && $_GET['action'] == 'getimages') {
    $silan_id = isset($_GET['silan_id']) ? (int)$_GET['silan_id'] : 0;
    
    if ($silan_id > 0) {
        $files = getSilanpusbayaFiles($dbs, $silan_id);
        $images = [];
        foreach ($files as $file) {
            if ($file['file_type'] == 'image') {
                $image_url = SWB . 'files/silanpusbaya/' . $file['file_name'];
                $images[] = [
                    'file_id' => $file['file_id'],
                    'file_url' => $image_url,
                    'file_original' => $file['file_original'],
                    'description' => $file['description'] ?? ''
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'images' => $images,
            'total' => count($images)
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'images' => [], 'total' => 0]);
    }
    exit();
}

// ============================================================
// HANDLE VIEW COUNTER
// ============================================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['action']) && $_GET['action'] == 'view') {
    $silan_id = isset($_GET['silan_id']) ? (int)$_GET['silan_id'] : 0;
    
    if ($silan_id > 0) {
        incrementSilanpusbayaView($dbs, $silan_id);
        $view_count = getSilanpusbayaViewCount($dbs, $silan_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'view_count' => $view_count
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
    }
    exit();
}

// ============================================================
// TAMPILAN OPAC
// ============================================================

// Get all prodi for tabs
$prodi_query = $db->query("
    SELECT p.prodi_id, p.desk_prodi, f.desk_faculty 
    FROM mst_prodi p 
    LEFT JOIN mst_faculty f ON p.faculty_id = f.faculty_id 
    ORDER BY f.desk_faculty, p.desk_prodi
");
$all_prodi = $prodi_query->fetchAll(\PDO::FETCH_ASSOC);

// Get all items for initial load
$sql_all = "SELECT DISTINCT s.* 
            FROM silanpusbaya s
            ORDER BY s.title ASC";
$all_items = $db->query($sql_all)->fetchAll(\PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('SILANPUSBAYA - Layanan Kemas Ulang Informasi'); ?></title>
    
    <style>
        .search-box, #searchBox, .opac-search-box, .search-form, .header-search,
        .sidebar, .filter-sidebar, .filter-panel {
            display: none !important;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0px;
            min-height: 100vh;
        }
        
        .silan-container { max-width: 1200px; margin: 0 auto; }
        
        .silan-header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .silan-header h1 {
            margin: 0 0 8px 0;
            color: #1a1a2e;
            font-size: 28px;
            font-weight: 700;
        }
        
        .silan-header p {
            color: #6c757d;
            font-size: 15px;
            margin: 0;
        }
        
        .silan-header .icon { font-size: 48px; margin-bottom: 10px; }
        
        .silan-search {
            display: flex;
            gap: 10px;
            max-width: 600px;
            margin: 15px auto 0;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .silan-search input {
            flex: 1;
            min-width: 200px;
            padding: 10px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.2s;
        }
        
        .silan-search input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
        }
        
        .silan-search button {
            padding: 10px 25px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .silan-search button:hover { background: #0b5ed7; }
        
        .silan-search .reset-btn {
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            display: none;
            transition: background 0.2s;
        }
        
        .silan-search .reset-btn:hover { background: #bb2d3b; }
        
        .result-info {
            color: #6c757d;
            margin-top: 15px;
            font-size: 14px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            display: none;
        }
        
        .result-info strong { color: #1a1a2e; }
        
        /* Tabs */
        .silan-tabs {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .silan-tabs .tab-btn {
            padding: 10px 20px;
            background: #f8f9fa;
            color: #495057;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .silan-tabs .tab-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .silan-tabs .tab-btn.active {
            background: #0d6efd;
            color: white;
        }
        
        .silan-tabs .tab-btn .badge {
            background: rgba(255,255,255,0.2);
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
            color: white;
        }
        
        .silan-tabs .tab-btn:not(.active) .badge {
            background: #e9ecef;
            color: #6c757d;
        }
        
        /* Card */
        .silan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border-color: #0d6efd;
        }
        
        /* Carousel Modal */
        #carouselModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.92);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        #carouselModal.active {
            display: flex;
        }
        
        .carousel-content {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 900px;
            height: 90vh;
            max-height: 750px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .carousel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            flex-shrink: 0;
        }
        
        .carousel-header h3 {
            margin: 0;
            font-size: 15px;
            color: #1a1a2e;
            font-weight: 600;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 15px;
        }
        
        .carousel-header .close-btn {
            background: none;
            border: none;
            color: #495057;
            font-size: 22px;
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.2s;
        }
        
        .carousel-header .close-btn:hover {
            color: #dc3545;
        }
        
        .carousel-body {
            flex: 1;
            background: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .carousel-body .slide-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        .carousel-body .slide-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: none;
            transition: opacity 0.5s ease;
        }
        
        .carousel-body .slide-container img.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .carousel-body .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
        }
        
        .carousel-body .loading .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        .carousel-body .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 15px 18px;
            font-size: 22px;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s;
            z-index: 10;
        }
        
        .carousel-body .nav-btn:hover {
            background: rgba(0,0,0,0.8);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-body .nav-btn.prev { left: 10px; }
        .carousel-body .nav-btn.next { right: 10px; }
        
        /* Indicators */
        .carousel-indicators {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 8px 15px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            flex-wrap: wrap;
        }
        
        .carousel-indicators .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ced4da;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            padding: 0;
        }
        
        .carousel-indicators .dot.active {
            background: #0d6efd;
            transform: scale(1.2);
        }
        
        .carousel-indicators .dot:hover {
            background: #adb5bd;
        }
        
        .carousel-indicators .dot.active:hover {
            background: #0d6efd;
        }
        
        .carousel-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            padding: 8px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            flex-shrink: 0;
            flex-wrap: wrap;
        }
        
        .carousel-footer .page-info {
            color: #495057;
            font-size: 13px;
            font-weight: 500;
        }
        
        .carousel-footer .img-desc {
            color: #6c757d;
            font-size: 12px;
            flex: 1;
            text-align: center;
            max-width: 60%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0d6efd;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .silan-header { padding: 20px; }
            .silan-search { flex-direction: column; }
            .silan-search input { min-width: auto; }
            .silan-tabs .tab-btn { padding: 6px 12px; font-size: 11px; }
            .silan-tabs .tab-btn .badge { font-size: 9px; padding: 1px 6px; }
            .carousel-content { height: 95vh; max-height: none; }
            .carousel-body .nav-btn { padding: 10px 12px; font-size: 16px; }
            .carousel-footer .img-desc { max-width: 80%; font-size: 11px; }
            .carousel-indicators .dot { width: 8px; height: 8px; }
        }
    </style>
</head>
<body>
<div class="silan-container">
    <div class="silan-header">
        <div class="icon">📚</div>
        <h1><?php echo __('SILANPUSBAYA'); ?></h1>
        <p><?php echo __('Sistem Informasi Layanan Perpustakaan - Kemas Ulang Informasi'); ?></p>
        
        <div class="silan-search" id="searchContainer">
            <input type="text" id="searchInput" placeholder="<?php echo __('Cari berdasarkan judul, subjek, atau deskripsi...'); ?>">
            <button type="button" id="searchBtn">🔍 <?php echo __('Cari'); ?></button>
            <button type="button" id="resetBtn" class="reset-btn">✕ <?php echo __('Setel ulang'); ?></button>
        </div>
        
        <div id="resultInfo" class="result-info">
            <?php echo __('Menampilkan hasil pencarian untuk') . ': <strong id="searchKeyword"></strong>'; ?>
            (<span id="resultCount">0</span> <?php echo __('ditemukan'); ?>)
        </div>
    </div>
    
    <div class="silan-tabs" id="tabContainer">
        <button class="tab-btn active" data-prodi="0">
            <?php echo __('Semua'); ?>
            <span class="badge"><?php echo count($all_items); ?></span>
        </button>
        <?php foreach ($all_prodi as $prodi): 
            $count = 0;
            foreach ($all_items as $item) {
                $prodi_ids = getSilanpusbayaProdi($dbs, $item['id']);
                if (in_array($prodi['prodi_id'], $prodi_ids)) {
                    $count++;
                }
            }
        ?>
            <button class="tab-btn" data-prodi="<?php echo $prodi['prodi_id']; ?>">
                <?php echo htmlspecialchars($prodi['desk_prodi']); ?>
                <span class="badge"><?php echo $count; ?></span>
            </button>
        <?php endforeach; ?>
    </div>
    
    <div id="silanGrid">
        <?php if (empty($all_items)): ?>
            <div style="text-align:center; padding:60px; background:white; border-radius:12px; color:#6c757d; font-size:18px;">
                <div style="font-size:64px;">📚</div>
                <p style="margin-top:20px;"><?php echo __('Belum ada data yang tersedia'); ?></p>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
                <?php foreach ($all_items as $item): 
                    $files = getSilanpusbayaFiles($dbs, $item['id']);
                    $thumbnail = null;
                    foreach ($files as $f) {
                        if ($f['file_type'] == 'image') {
                            $thumbnail = $f;
                            break;
                        }
                    }
                ?>
                    <div class="silan-card" style="background:white; border-radius:12px; padding:20px; border:1px solid #e9ecef; transition:all 0.3s; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                        <?php if ($thumbnail): ?>
                            <div style="text-align:center; margin-bottom:15px; background:#f0f2f5; border-radius:8px; padding:10px; cursor:pointer;" 
                                 onclick="openCarousel(<?php echo $item['id']; ?>, <?php echo $thumbnail['file_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['title'])); ?>')">
                                <img src="<?php echo SWB; ?>files/silanpusbaya/<?php echo $thumbnail['file_name']; ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     style="width:100%; max-height:180px; object-fit:contain; border-radius:4px;">
                            </div>
                        <?php else: ?>
                            <div style="text-align:center; padding:40px; background:#f8f9fa; border-radius:8px; margin-bottom:15px; color:#999;">
                                📄 <?php echo __('No Image'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3 style="margin:0 0 8px 0; font-size:17px; color:#1a1a2e;">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        
                        <?php if (!empty($item['subject'])): ?>
                            <div style="font-size:13px; color:#6c757d; margin-bottom:5px;">
                                <strong><?php echo __('Subjek'); ?>:</strong> <?php echo htmlspecialchars($item['subject']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['description'])): ?>
                            <div style="font-size:14px; color:#495057; line-height:1.6; margin:10px 0;">
                                <?php 
                                $desc = strip_tags($item['description']);
                                if (strlen($desc) > 100) {
                                    echo nl2br(htmlspecialchars(substr($desc, 0, 100))) . '...';
                                    echo ' <a href="javascript:void(0)" onclick="toggleDescription(this)" style="color:#0d6efd; text-decoration:none; font-weight:500;">' . __('selengkapnya') . '</a>';
                                    echo '<div class="desc-full" style="display:none; margin-top:5px;">' . nl2br(htmlspecialchars($desc)) . '</div>';
                                } else {
                                    echo nl2br(htmlspecialchars($desc));
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($files)): ?>
                            <div style="margin-top:15px; border-top:1px solid #e9ecef; padding-top:12px;">
                                <div style="font-size:13px; font-weight:600; color:#495057; margin-bottom:8px;">
                                    📎 <?php echo __('File Terkait'); ?> (<?php echo count($files); ?>)
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    <?php foreach ($files as $file): 
                                        $file_url = SWB . 'files/silanpusbaya/' . $file['file_name'];
                                        if ($file['file_type'] == 'image'):
                                    ?>
                                        <a href="javascript:void(0)" onclick="openCarousel(<?php echo $item['id']; ?>, <?php echo $file['file_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['title'])); ?>')" 
                                           style="display:inline-block; padding:4px 10px; background:#e8f0fe; color:#1a73e8; border-radius:6px; text-decoration:none; font-size:11px; transition:all 0.2s;">
                                            🖼️ <?php echo htmlspecialchars(substr($file['file_original'], 0, 15)) . (strlen($file['file_original']) > 15 ? '...' : ''); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo $file_url; ?>" target="_blank" 
                                           style="display:inline-block; padding:4px 10px; background:#fce8e6; color:#d93025; border-radius:6px; text-decoration:none; font-size:11px; transition:all 0.2s;">
                                            📄 <?php echo htmlspecialchars(substr($file['file_original'], 0, 15)) . (strlen($file['file_original']) > 15 ? '...' : ''); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; background:#f8f9fa; color:#6c757d;">
                                👁️ <?php echo number_format($item['view_count']); ?>
                            </span>
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; background:#e9ecef; color:#6c757d;">
                                <?php echo date('d M Y', strtotime($item['upload_date'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Carousel Modal -->
<div id="carouselModal">
    <div class="carousel-content">
        <div class="carousel-header">
            <h3 id="carouselTitle">🖼️ <?php echo __('Galeri Gambar'); ?></h3>
            <button class="close-btn" onclick="closeCarousel()">✕</button>
        </div>
        <div class="carousel-body" id="carouselBody">
            <div class="loading" id="carouselLoading">
                <div class="spinner"></div>
                <p><?php echo __('Memuat gambar...'); ?></p>
            </div>
            <div class="slide-container" id="slideContainer">
                <img id="carouselImage" src="" alt="">
            </div>
            <button class="nav-btn prev" id="carouselPrev" onclick="carouselPrev()" style="display:none;">◀</button>
            <button class="nav-btn next" id="carouselNext" onclick="carouselNext()" style="display:none;">▶</button>
        </div>
        <div class="carousel-indicators" id="carouselIndicators"></div>
        <div class="carousel-footer">
            <span class="page-info" id="carouselPageInfo">0 / 0</span>
            <span class="img-desc" id="carouselDesc"></span>
        </div>
    </div>
</div>

<script>
// ============================================================
// 1. TOGGLE DESKRIPSI
// ============================================================
function toggleDescription(link) {
    var container = link.parentElement;
    var fullDesc = container.querySelector('.desc-full');
    if (fullDesc.style.display === 'none') {
        fullDesc.style.display = 'block';
        link.textContent = '<?php echo __('sembunyikan'); ?>';
        link.style.display = 'inline';
    } else {
        fullDesc.style.display = 'none';
        link.textContent = '<?php echo __('selengkapnya'); ?>';
    }
}

// ============================================================
// 2. CAROUSEL
// ============================================================
var carouselImages = [];
var carouselCurrentIndex = 0;
var carouselSilanId = 0;
var carouselInterval = null;

function openCarousel(silanId, fileId, title) {
    carouselSilanId = silanId;
    var modal = document.getElementById('carouselModal');
    var titleEl = document.getElementById('carouselTitle');
    var img = document.getElementById('carouselImage');
    var loading = document.getElementById('carouselLoading');
    var pageInfo = document.getElementById('carouselPageInfo');
    var desc = document.getElementById('carouselDesc');
    var prevBtn = document.getElementById('carouselPrev');
    var nextBtn = document.getElementById('carouselNext');
    var indicators = document.getElementById('carouselIndicators');
    
    titleEl.textContent = '🖼️ ' + (title || '<?php echo __('Galeri Gambar'); ?>');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Reset
    img.style.display = 'none';
    img.src = '';
    loading.style.display = 'block';
    prevBtn.style.display = 'none';
    nextBtn.style.display = 'none';
    pageInfo.textContent = '0 / 0';
    desc.textContent = '';
    indicators.innerHTML = '';
    
    // Stop interval sebelumnya
    if (carouselInterval) {
        clearInterval(carouselInterval);
        carouselInterval = null;
    }
    
    // Ambil semua gambar
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?p=silanpusbaya&ajax=1&action=getimages&silan_id=' + encodeURIComponent(silanId), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success && Array.isArray(response.images) && response.images.length > 0) {
                    carouselImages = response.images;
                    var startIndex = 0;
                    for (var i = 0; i < carouselImages.length; i++) {
                        if (String(carouselImages[i].file_id) === String(fileId)) {
                            startIndex = i;
                            break;
                        }
                    }
                    carouselCurrentIndex = startIndex;
                    showCarouselImage(carouselCurrentIndex);
                    
                    // Auto slide setiap 3 detik
                    carouselInterval = setInterval(function() {
                        carouselNext();
                    }, 3000);
                } else {
                    loading.innerHTML = '<div style="font-size:48px; color:#dc3545;">❌</div><p style="color:#dc3545;"><?php echo __('Tidak ada gambar'); ?></p>';
                }
            } catch(e) {
                console.error('Invalid getimages response:', xhr.responseText, e);
                loading.innerHTML = '<div style="font-size:48px; color:#dc3545;">❌</div><p style="color:#dc3545;"><?php echo __('Respons gambar tidak valid'); ?></p>';
            }
        } else {
            loading.innerHTML = '<div style="font-size:48px; color:#dc3545;">❌</div><p style="color:#dc3545;"><?php echo __('Gagal memuat gambar'); ?> (' + xhr.status + ')</p>';
        }
    };
    xhr.onerror = function() {
        loading.innerHTML = '<div style="font-size:48px; color:#dc3545;">❌</div><p style="color:#dc3545;"><?php echo __('Kesalahan jaringan saat memuat gambar'); ?></p>';
    };
    xhr.send();
}

function showCarouselImage(index) {
    var img = document.getElementById('carouselImage');
    var loading = document.getElementById('carouselLoading');
    var pageInfo = document.getElementById('carouselPageInfo');
    var desc = document.getElementById('carouselDesc');
    var prevBtn = document.getElementById('carouselPrev');
    var nextBtn = document.getElementById('carouselNext');
    var indicators = document.getElementById('carouselIndicators');
    
    if (index >= 0 && index < carouselImages.length) {
        var imageUrl = carouselImages[index].file_url;
        
        img.onload = function() {
            loading.style.display = 'none';
            img.style.display = 'block';
        };
        
        img.onerror = function() {
            loading.innerHTML = '<div style="font-size:48px; color:#dc3545;">❌</div><p style="color:#dc3545;"><?php echo __('Gagal memuat gambar'); ?></p>';
        };
        
        img.src = imageUrl;
        pageInfo.textContent = (index + 1) + ' / ' + carouselImages.length;
        desc.textContent = carouselImages[index].description || '';
        
        // Show/hide navigation buttons
        prevBtn.style.display = (carouselImages.length > 1 && index > 0) ? 'block' : 'none';
        nextBtn.style.display = (carouselImages.length > 1 && index < carouselImages.length - 1) ? 'block' : 'none';
        
        // Update indicators
        indicators.innerHTML = '';
        for (var i = 0; i < carouselImages.length; i++) {
            var dot = document.createElement('button');
            dot.className = 'dot' + (i === index ? ' active' : '');
            dot.setAttribute('data-index', i);
            dot.onclick = function() {
                var idx = parseInt(this.getAttribute('data-index'));
                carouselCurrentIndex = idx;
                showCarouselImage(idx);
                // Reset auto slide
                if (carouselInterval) {
                    clearInterval(carouselInterval);
                    carouselInterval = setInterval(function() {
                        carouselNext();
                    }, 3000);
                }
            };
            indicators.appendChild(dot);
        }
        
        // Update view counter
        var viewXhr = new XMLHttpRequest();
        viewXhr.open('GET', '<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?p=silanpusbaya&ajax=1&action=view&silan_id=' + encodeURIComponent(carouselSilanId), true);
        viewXhr.send();
    }
}

function carouselNext() {
    if (carouselCurrentIndex < carouselImages.length - 1) {
        carouselCurrentIndex++;
        showCarouselImage(carouselCurrentIndex);
    } else {
        // Loop ke awal
        carouselCurrentIndex = 0;
        showCarouselImage(carouselCurrentIndex);
    }
}

function carouselPrev() {
    if (carouselCurrentIndex > 0) {
        carouselCurrentIndex--;
        showCarouselImage(carouselCurrentIndex);
    }
}

function closeCarousel() {
    var modal = document.getElementById('carouselModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    var img = document.getElementById('carouselImage');
    img.src = '';
    img.style.display = 'none';
    carouselImages = [];
    carouselCurrentIndex = 0;
    carouselSilanId = 0;
    
    if (carouselInterval) {
        clearInterval(carouselInterval);
        carouselInterval = null;
    }
    
    document.getElementById('carouselPrev').style.display = 'none';
    document.getElementById('carouselNext').style.display = 'none';
    document.getElementById('carouselIndicators').innerHTML = '';
    
    var loading = document.getElementById('carouselLoading');
    loading.style.display = 'block';
    loading.innerHTML = '<div class="spinner"></div><p><?php echo __('Memuat gambar...'); ?></p>';
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    var modal = document.getElementById('carouselModal');
    if (!modal.classList.contains('active')) return;
    
    if (e.key === 'Escape') {
        closeCarousel();
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        carouselNext();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        carouselPrev();
    }
});

// ============================================================
// 3. AJAX SEARCH & TAB
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    var searchBtn = document.getElementById('searchBtn');
    var resetBtn = document.getElementById('resetBtn');
    var grid = document.getElementById('silanGrid');
    var resultInfo = document.getElementById('resultInfo');
    var searchKeyword = document.getElementById('searchKeyword');
    var resultCount = document.getElementById('resultCount');
    var tabBtns = document.querySelectorAll('.tab-btn');
    var currentProdi = 0;
    
    function performSearch(query, prodiId) {
        grid.style.opacity = '0.5';
        grid.innerHTML = '<div style="text-align:center; padding:40px;"><div class="spinner"></div><p style="margin-top:10px; color:#6c757d;"><?php echo __('Sedang mencari...'); ?></p></div>';
        
        var url = 'index.php?p=silanpusbaya&ajax=1';
        if (query && query.trim() !== '') {
            url += '&search=' + encodeURIComponent(query);
        }
        if (prodiId && prodiId !== 0) {
            url += '&prodi_id=' + encodeURIComponent(prodiId);
        }
        
        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                grid.style.opacity = '1';
                if (data.success) {
                    grid.innerHTML = data.html;
                    
                    if (query && query.trim() !== '') {
                        resultInfo.style.display = 'block';
                        searchKeyword.textContent = query;
                        resultCount.textContent = data.total || 0;
                        resetBtn.style.display = 'inline-block';
                    } else {
                        resultInfo.style.display = 'none';
                        resetBtn.style.display = 'none';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                grid.style.opacity = '1';
                grid.innerHTML = '<div style="text-align:center; padding:40px; color:#dc3545;"><?php echo __('Terjadi kesalahan saat mencari data'); ?></div>';
            });
    }
    
    searchBtn.addEventListener('click', function() {
        var query = searchInput.value.trim();
        performSearch(query, currentProdi);
    });
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchBtn.click();
        }
    });
    
    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        performSearch('', currentProdi);
    });
    
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabBtns.forEach(function(b) {
                b.classList.remove('active');
                b.style.background = '#f8f9fa';
                b.style.color = '#495057';
            });
            this.classList.add('active');
            this.style.background = '#0d6efd';
            this.style.color = 'white';
            
            currentProdi = parseInt(this.dataset.prodi) || 0;
            var query = searchInput.value.trim();
            performSearch(query, currentProdi);
        });
    });
});
</script>
</body>
</html>