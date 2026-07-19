<?php
/*
 * File: silanpusbaya_admin.inc.php
 * Created on Sun Jul 19 2026
 * Last Updated: Sun Jul 19 2026 10:13:42 AM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 */

/**
 * Admin Page - Plugins Kemas Ulang Informasi SLiMS
 */

use SLiMS\DB;

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');

// start the session
require SB . 'admin/default/session.inc.php';
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO . 'simbio_DB/simbio_dbop.inc.php';

// Load helper
require_once __DIR__ . '/../helper.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');
$can_write = utility::havePrivilege('bibliography', 'w');

if (!$can_read) {
    die('<div class="errorBox">' . __('You don\'t have enough privileges to access this area!') . '</div>');
}

$plugin_id = $_GET['id'] ?? '';
$plugin_mod = $_GET['mod'] ?? '';

$selfUrl = pluginUrl(reset: true);
$selfUrlWithQuery = $selfUrl . (($_SERVER['QUERY_STRING'] ?? '') ? '&' . $_SERVER['QUERY_STRING'] : '');

// Path untuk upload file
$upload_dir = SB . 'files/silanpusbaya/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* =========================
 * SAVE / UPDATE
 * ========================= */
if (isset($_POST['saveData']) && $can_write) {
    $title = trim(strip_tags($_POST['title'] ?? ''));
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $description = trim(strip_tags($_POST['description'] ?? ''));
    $prodi_ids = $_POST['prodi_ids'] ?? [];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($title === '') {
        utility::jsToastr(__('SILANPUSBAYA'), __('Title cannot be empty!'), 'error');
        exit();
    }

    $db = DB::getInstance();
    $now = date('Y-m-d H:i:s');
    $uid = $_SESSION['uid'] ?? 0;

    $query_str = $_POST['lastQueryStr'] ?? ('id=' . $plugin_id . '&mod=' . $plugin_mod);

    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE silanpusbaya SET 
                title = ?,
                subject = ?,
                description = ?,
                last_update = ?,
                uid = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $update = $stmt->execute([$title, $subject, $description, $now, $uid, $id]);
        
        // Update prodi
        $db->query("DELETE FROM silanpusbaya_prodi WHERE silanpusbaya_id='{$id}'");
        foreach ($prodi_ids as $prodi_id) {
            if ($prodi_id > 0) {
                $db->query("INSERT INTO silanpusbaya_prodi (silanpusbaya_id, prodi_id) VALUES ('{$id}', '" . (int)$prodi_id . "')");
            }
        }
        
        if ($update) {
            utility::jsToastr(__('SILANPUSBAYA'), __('Data successfully updated'), 'success');
        } else {
            utility::jsToastr(__('SILANPUSBAYA'), __('Update failed'), 'error');
        }
    } else {
        // INSERT
        $sql = "INSERT INTO silanpusbaya (title, subject, description, upload_date, last_update, uid) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $insert = $stmt->execute([$title, $subject, $description, $now, $now, $uid]);
        $id = $db->lastInsertId();
        
        // Insert prodi
        foreach ($prodi_ids as $prodi_id) {
            if ($prodi_id > 0) {
                $db->query("INSERT INTO silanpusbaya_prodi (silanpusbaya_id, prodi_id) VALUES ('{$id}', '" . (int)$prodi_id . "')");
            }
        }
        
        if ($insert) {
            utility::jsToastr(__('SILANPUSBAYA'), __('New data successfully saved'), 'success');
        } else {
            utility::jsToastr(__('SILANPUSBAYA'), __('Save failed'), 'error');
        }
    }

    // Redirect ke daftar setelah save/update
    echo '<script>parent.$("#mainContent").simbioAJAX("' . $selfUrl . '&' . $query_str . '");</script>';
    exit();
}

/* =========================
 * DELETE
 * ========================= */
if (isset($_POST['itemID']) && isset($_POST['itemAction']) && $can_write) {
    $db = DB::getInstance();
    $ids = $_POST['itemID'];
    if (!is_array($ids)) {
        $ids = [$ids];
    }

    $deleted = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        
        // Get and delete files
        $files = $db->query("SELECT file_name FROM silanpusbaya_files WHERE silanpusbaya_id='{$id}'");
        if ($files) {
            while ($file = $files->fetch(\PDO::FETCH_ASSOC)) {
                if (file_exists($upload_dir . $file['file_name'])) {
                    unlink($upload_dir . $file['file_name']);
                }
            }
        }
        
        $db->query("DELETE FROM silanpusbaya_files WHERE silanpusbaya_id='{$id}'");
        $db->query("DELETE FROM silanpusbaya_prodi WHERE silanpusbaya_id='{$id}'");
        $stmt = $db->prepare("DELETE FROM silanpusbaya WHERE id = ?");
        if ($stmt->execute([$id])) {
            $deleted++;
        }
    }

    if ($deleted > 0) {
        utility::jsToastr(__('SILANPUSBAYA'), __('Data deleted successfully'), 'success');
    } else {
        utility::jsToastr(__('SILANPUSBAYA'), __('Delete failed'), 'error');
    }

    echo '<script>parent.$("#mainContent").simbioAJAX("' . $selfUrl . '&id=' . $plugin_id . '&mod=' . $plugin_mod . '");</script>';
    exit();
}

/* =========================
 * DELETE FILE - TETAP DI HALAMAN DETAIL
 * ========================= */
if (isset($_GET['action']) && $_GET['action'] == 'deletefile' && isset($_GET['file_id']) && $can_write) {
    $file_id = (int)$_GET['file_id'];
    $db = DB::getInstance();
    
    $query = $db->query("SELECT file_name, silanpusbaya_id FROM silanpusbaya_files WHERE file_id='{$file_id}'");
    if ($query && $query->rowCount() > 0) {
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        if (file_exists($upload_dir . $data['file_name'])) {
            unlink($upload_dir . $data['file_name']);
        }
        $db->query("DELETE FROM silanpusbaya_files WHERE file_id='{$file_id}'");
        utility::jsToastr(__('SILANPUSBAYA'), __('File deleted successfully'), 'success');
    }
    
    // Tetap di halaman detail
    $silan_id = $data['silanpusbaya_id'] ?? 0;
    $detailUrl = $selfUrl . '&action=detail&id=' . $silan_id . '&id=' . $plugin_id . '&mod=' . $plugin_mod;
    echo '<script>parent.$("#mainContent").simbioAJAX("' . $detailUrl . '");</script>';
    exit();
}

/* =========================
 * UPLOAD FILE (AJAX) - TETAP DI HALAMAN DETAIL
 * ========================= */
if (isset($_POST['uploadFiles']) && $can_write) {
    $silanpusbaya_id = isset($_POST['silanpusbaya_id']) ? (int)$_POST['silanpusbaya_id'] : 0;
    
    if ($silanpusbaya_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit();
    }
    
    $db = DB::getInstance();
    $now = date('Y-m-d H:i:s');
    $uploaded = [];
    $errors = [];
    
    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] == 0) {
                $file_original = $_FILES['files']['name'][$key];
                $file_size = $_FILES['files']['size'][$key];
                $file_ext = strtolower(pathinfo($file_original, PATHINFO_EXTENSION));
                $file_type = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) ? 'image' : 'pdf';
                
                $file_name = 'silan_' . time() . '_' . md5($file_original . microtime()) . '.' . $file_ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    $sql = "INSERT INTO silanpusbaya_files 
                            (silanpusbaya_id, file_name, file_original, file_type, file_size, upload_date) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$silanpusbaya_id, $file_name, $file_original, $file_type, $file_size, $now]);
                    $uploaded[] = $file_original;
                } else {
                    $errors[] = $file_original . ' - Failed to move file';
                }
            }
        }
    } else {
        $errors[] = 'No files selected';
    }
    
    // Kirim response JSON
    echo json_encode([
        'success' => count($uploaded) > 0,
        'uploaded' => $uploaded,
        'errors' => $errors,
        'message' => count($uploaded) . ' file(s) uploaded',
        'silanpusbaya_id' => $silanpusbaya_id
    ]);
    exit();
}

/* =========================
 * UI HEADER
 * ========================= */
?>
<div class="menuBox">
    <div class="menuBoxInner masterFileIcon">
        <div class="per_title"><h2><?php echo __('SILANPUSBAYA'); ?></h2></div>
        <div class="sub_section">
            <div class="btn-group">
                <a href="<?php echo $selfUrl; ?>" class="btn btn-default"><?php echo __('Data List'); ?></a>
                <a href="<?php echo $selfUrl; ?>&action=detail" class="btn btn-default"><?php echo __('Add New'); ?></a>
            </div>
            <form action="<?php echo $selfUrl; ?>" method="get" class="form-inline">
                <?php echo __('Search'); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($plugin_id); ?>">
                <input type="hidden" name="mod" value="<?php echo htmlspecialchars($plugin_mod); ?>">
                <input type="text" name="keywords" class="form-control col-md-3" value="<?php echo htmlspecialchars($_GET['keywords'] ?? ''); ?>">
                <input type="submit" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default">
            </form>
        </div>
    </div>
</div>

<?php

/* =========================
 * FORM DETAIL
 * ========================= */
if (isset($_POST['detail']) || (isset($_GET['action']) && $_GET['action'] === 'detail')) {
    if (!$can_write) {
        die('<div class="errorBox">' . __('No write access') . '</div>');
    }

    $db = DB::getInstance();
    $itemID = (int)($_POST['itemID'] ?? $_GET['id'] ?? 0);
    $rec_d = [];

    if ($itemID > 0) {
        $query = $db->query("SELECT * FROM silanpusbaya WHERE id='{$itemID}'");
        if ($query && $query->rowCount() > 0) {
            $rec_d = $query->fetch(\PDO::FETCH_ASSOC);
        }
    }
    
    $selected_prodi = getSilanpusbayaProdi($dbs, $itemID);
    
    // Get prodi grouped by faculty menggunakan fungsi helper
    $grouped_prodi = getGroupedProdi($dbs);

    $form = new simbio_form_table_AJAX('mainForm', $selfUrlWithQuery, 'post', 'enctype="multipart/form-data"');
    $form->table_attr = 'id="dataList" class="s-table table"';
    $form->table_header_attr = 'class="alterCell font-weight-bold"';
    $form->table_content_attr = 'class="alterCell2"';

    $form->addHidden('id', $plugin_id);
    $form->addHidden('mod', $plugin_mod);
    $form->addHidden('lastQueryStr', $_SERVER['QUERY_STRING'] ?? '');

    if ($itemID > 0 && !empty($rec_d)) {
        $form->edit_mode = true;
        $form->record_id = $itemID;
        $form->record_title = $rec_d['title'] ?? '';
        $form->submit_button_attr = 'name="saveData" value="' . __('Update') . '" class="s-btn btn btn-primary"';
        $form->addHidden('id', $itemID);
    } else {
        $form->submit_button_attr = 'name="saveData" value="' . __('Save') . '" class="s-btn btn btn-default"';
        $form->addHidden('id', '0');
    }

    // Judul
    $form->addTextField('text', 'title', __('Title') . '*', $rec_d['title'] ?? '', 'style="width:60%;" class="form-control" required');

    // Subjek
    $form->addTextField('text', 'subject', __('Subject'), $rec_d['subject'] ?? '', 'style="width:60%;" class="form-control"');

    // Deskripsi
    $form->addTextField('textarea', 'description', __('Description'), $rec_d['description'] ?? '', 'style="width:60%; height:150px;" class="form-control"');

    // Relasi Prodi - Checkbox grouped by faculty
    $prodi_html = '<div style="max-height:300px; overflow-y:auto; border:1px solid #ddd; padding:10px; border-radius:4px; width:60%;">';
    if (empty($grouped_prodi)) {
        $prodi_html .= '<div style="color:#999;">' . __('No study program available') . '</div>';
    } else {
        foreach ($grouped_prodi as $faculty_name => $prodi_list) {
            $prodi_html .= '<div style="margin-bottom:10px;">';
            $prodi_html .= '<strong style="color:#2c3e50; display:block; padding:5px 0; border-bottom:1px solid #eee; margin-bottom:5px;">' . htmlspecialchars($faculty_name) . '</strong>';
            foreach ($prodi_list as $prodi) {
                $checked = in_array($prodi['prodi_id'], $selected_prodi) ? 'checked' : '';
                $prodi_html .= '<label style="display:inline-block; margin-right:15px; margin-bottom:5px; font-weight:normal; font-size:13px;">';
                $prodi_html .= '<input type="checkbox" name="prodi_ids[]" value="' . $prodi['prodi_id'] . '" ' . $checked . '> ';
                $prodi_html .= htmlspecialchars($prodi['desk_prodi']);
                $prodi_html .= '</label>';
            }
            $prodi_html .= '</div>';
        }
    }
    $prodi_html .= '</div>';
    
    $form->addAnything(__('Program Study'), $prodi_html);

    // ============================================================
    // UPLOAD AREA SEBELUM TOMBOL SIMPAN - MULTIPLE FILES
    // ============================================================
    
    $upload_html = '';
    if ($itemID > 0) {
        $files = getSilanpusbayaFiles($dbs, $itemID);
        
        // Preview files yang sudah diupload
        $preview_html = '';
        if (!empty($files)) {
            $preview_html .= '<div style="margin-top:15px; margin-bottom:15px;">';
            $preview_html .= '<h5 style="margin:0 0 10px 0; color:#2c3e50;">📁 ' . __('Uploaded Files') . ' (' . count($files) . ')</h5>';
            $preview_html .= '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:10px;">';
            foreach ($files as $file) {
                $file_url = SWB . 'files/silanpusbaya/' . $file['file_name'];
                $preview_html .= '<div style="border:1px solid #ddd; border-radius:8px; padding:10px; background:#fff; text-align:center; position:relative;">';
                if ($file['file_type'] == 'image') {
                    $preview_html .= '<img src="' . $file_url . '" style="width:100%; height:100px; object-fit:cover; border-radius:4px; margin-bottom:5px;">';
                } else {
                    $preview_html .= '<div style="font-size:40px; margin:10px 0;">📄</div>';
                }
                $preview_html .= '<div style="font-size:11px; color:#6c757d; word-break:break-all;">' . htmlspecialchars($file['file_original']) . '</div>';
                $preview_html .= '<div style="margin-top:5px;">';
                $preview_html .= '<a href="' . $file_url . '" target="_blank" class="btn btn-xs btn-info" title="' . __('View') . '">👁️</a> ';
                $preview_html .= '<a href="' . $selfUrl . '&action=deletefile&file_id=' . $file['file_id'] . '&id=' . $plugin_id . '&mod=' . $plugin_mod . '" class="btn btn-xs btn-danger" onclick="return confirm(\'' . __('Are you sure?') . '\')" title="' . __('Delete') . '">✕</a>';
                $preview_html .= '</div>';
                $preview_html .= '</div>';
            }
            $preview_html .= '</div></div>';
        }
        
        $upload_html = '
        <div style="margin-top:15px; padding:15px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9;">
            <h4 style="margin-top:0;">📎 ' . __('Upload Multiple Files') . '</h4>
            <p style="color:#6c757d; font-size:13px; margin-bottom:10px;">' . __('Supported: Images (JPG, PNG, GIF, WEBP, SVG) and PDF files. You can select multiple files at once.') . '</p>
            
            ' . $preview_html . '
            
            <div id="uploadContainer">
                <input type="file" name="files[]" id="fileInput" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf" style="display:inline-block; padding:5px; width:300px;">
                <button type="button" onclick="uploadFiles()" class="btn btn-primary btn-sm">' . __('Upload') . '</button>
                <input type="hidden" id="silanId" value="' . $itemID . '">
                
                <div id="uploadProgress" style="display:none; margin-top:10px;">
                    <div style="background:#e9ecef; border-radius:4px; height:20px; overflow:hidden;">
                        <div id="progressBar" style="background:#3498db; height:100%; width:0%; transition:width 0.3s;"></div>
                    </div>
                    <span id="progressText" style="font-size:12px; color:#6c757d;">0%</span>
                </div>
                <div id="uploadStatus" style="margin-top:10px; display:none;"></div>
            </div>
        </div>
        ';
    } else {
        $upload_html = '
        <div style="margin-top:15px; padding:15px; border:1px dashed #ddd; border-radius:4px; background:#fcfcfc;">
            <p style="color:#999; margin:0;">📎 ' . __('Upload files available after saving the data first') . '</p>
        </div>
        ';
    }
    
    // Tambahkan upload area ke form sebelum tombol submit
    $form->addAnything('', $upload_html);

    echo $form->printOut();
    
    // JavaScript untuk upload multiple files - TETAP DI HALAMAN DETAIL
    if ($itemID > 0) {
        ?>
        <script type="text/javascript">
        function uploadFiles() {
            var fileInput = document.getElementById('fileInput');
            var files = fileInput.files;
            var silanId = document.getElementById('silanId').value;
            
            if (files.length === 0) {
                alert('<?php echo __('Please select files to upload'); ?>');
                return;
            }
            
            var formData = new FormData();
            for (var i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            formData.append('uploadFiles', 1);
            formData.append('silanpusbaya_id', silanId);
            
            var progressDiv = document.getElementById('uploadProgress');
            var progressBar = document.getElementById('progressBar');
            var progressText = document.getElementById('progressText');
            var statusDiv = document.getElementById('uploadStatus');
            
            progressDiv.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
            statusDiv.style.display = 'none';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $selfUrl; ?>&id=<?php echo $plugin_id; ?>&mod=<?php echo $plugin_mod; ?>', true);
            
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.textContent = percent + '%';
                }
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        statusDiv.style.display = 'block';
                        if (response.success) {
                            statusDiv.innerHTML = '<div style="color:#28a745; padding:10px; background:#d4edda; border-radius:4px;">✅ ' + response.message + '</div>';
                            // Reset file input
                            fileInput.value = '';
                            // Reload halaman detail menggunakan simbioAJAX
                            var detailUrl = '<?php echo $selfUrl; ?>&action=detail&id=' + silanId + '&id=<?php echo $plugin_id; ?>&mod=<?php echo $plugin_mod; ?>';
                            parent.$("#mainContent").simbioAJAX(detailUrl);
                        } else {
                            statusDiv.innerHTML = '<div style="color:#dc3545; padding:10px; background:#f8d7da; border-radius:4px;">❌ Upload failed: ' + (response.message || 'Unknown error') + '</div>';
                        }
                    } catch(e) {
                        statusDiv.innerHTML = '<div style="color:#dc3545; padding:10px; background:#f8d7da; border-radius:4px;">❌ Error: ' + e.message + '</div>';
                    }
                } else {
                    statusDiv.style.display = 'block';
                    statusDiv.innerHTML = '<div style="color:#dc3545; padding:10px; background:#f8d7da; border-radius:4px;">❌ Upload failed. Server error (Status: ' + xhr.status + ')</div>';
                }
            };
            
            xhr.onerror = function() {
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<div style="color:#dc3545; padding:10px; background:#f8d7da; border-radius:4px;">❌ Upload failed. Network error.</div>';
            };
            
            xhr.send(formData);
        }
        </script>
        <?php
    }
    
    return;
}

/* =========================
 * LIST (DataGrid)
 * ========================= */
$table_spec = 'silanpusbaya';
$datagrid = new simbio_datagrid();

$datagrid->setSQLColumn(
    'id',
    'title AS \'' . __('Title') . '\'',
    'subject AS \'' . __('Subject') . '\'',
    'description AS \'' . __('Description') . '\'',
    'view_count AS \'' . __('Views') . '\'',
    'upload_date AS \'' . __('Upload Date') . '\''
);

$datagrid->setSQLorder('id DESC');

if (!empty($_GET['keywords'])) {
    $keywords = utility::filterData('keywords', 'get', true, true, true);
    $datagrid->setSQLCriteria("title LIKE '%{$keywords}%' OR subject LIKE '%{$keywords}%' OR description LIKE '%{$keywords}%'");
}

$datagrid->table_attr = 'id="dataList" class="s-table table"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight:bold;"';
$datagrid->chbox_form_URL = $selfUrl;

$datagrid->custom_columns = [
    'Views' => function($row) {
        return '<span class="badge" style="background:#3498db; color:white; padding:3px 10px; border-radius:20px; font-size:13px;">' . number_format($row['view_count']) . '</span>';
    },
    'Actions' => function($row) use ($selfUrl, $plugin_id, $plugin_mod) {
        return '<div class="btn-group btn-group-xs">
                    <a href="' . $selfUrl . '&action=detail&id=' . $row['id'] . '&id=' . $plugin_id . '&mod=' . $plugin_mod . '" class="btn btn-warning" title="' . __('Edit') . '">
                        <span class="glyphicon glyphicon-edit"></span>
                    </a>
                    <form action="' . $selfUrl . '" method="post" style="display:inline;" onsubmit="return confirm(\'' . __('Are you sure want to delete this data?') . '\')">
                        <input type="hidden" name="id" value="' . $plugin_id . '">
                        <input type="hidden" name="mod" value="' . $plugin_mod . '">
                        <input type="hidden" name="itemAction" value="delete">
                        <input type="hidden" name="itemID[]" value="' . $row['id'] . '">
                        <button type="submit" class="btn btn-danger" title="' . __('Delete') . '">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                    </form>
                </div>';
    }
];

echo $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read && $can_write));