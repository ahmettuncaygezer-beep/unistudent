<?php
$PAGE_TITLE = 'Veritabanı Araçları';
require_once 'header.php';
require_once 'api/database.php';
ensureTables();
$db = getDB();

// Handle backup
if(isset($_GET['action']) && $_GET['action'] === 'backup'){
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- ÜniBütçe Database Backup\n-- Generated: ".date('Y-m-d H:i:s')."\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach($tables as $table){
        $res = $db->query("SELECT * FROM `$table`");
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1].";\n\n";
        while($row = $res->fetch(PDO::FETCH_NUM)){
            $vals = array_map(fn($v) => $v===null?'NULL':'"'.addslashes(str_replace("\n","\\n",$v)).'"', $row);
            $sql .= "INSERT INTO `$table` VALUES(".implode(',',$vals).");\n";
        }
        $sql .= "\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="unistudent_backup_'.date('Y_m_d_His').'.sql"');
    echo $sql; exit;
}

// Table stats
$tablesInfo = $db->query("SHOW TABLE STATUS")->fetchAll(PDO::FETCH_ASSOC);
$totalSize  = array_sum(array_map(fn($t)=>($t['Data_length']+$t['Index_length']), $tablesInfo));
$totalRows  = array_sum(array_map(fn($t)=>(int)$t['Rows'], $tablesInfo));
?>

<!-- KPIs -->
<div class="adm-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-table"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo count($tablesInfo); ?></div><div class="adm-kpi-label">Tablo Sayısı</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-list"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo number_format($totalRows); ?></div><div class="adm-kpi-label">Toplam Kayıt</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-hard-drive"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo number_format($totalSize/1024/1024,2); ?> MB</div><div class="adm-kpi-label">Toplam Boyut</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1rem;">utf8mb4</div><div class="adm-kpi-label">Karakter Seti</div></div>
    </div>
</div>

<div class="adm-section-header">
    <div>
        <div class="adm-section-title">Tablo Listesi</div>
        <div class="adm-section-desc">Canlı veritabanı durumu</div>
    </div>
    <a href="?action=backup" class="adm-btn adm-btn-success">
        <i class="fa-solid fa-download"></i> SQL Yedek Al
    </a>
</div>

<div class="adm-table-wrap">
    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Tablo Adı</th>
                    <th style="text-align:center;">Satır</th>
                    <th style="text-align:center;">Boyut</th>
                    <th>Boyut Göstergesi</th>
                    <th>Karakter Seti</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($tablesInfo as $t):
                    $sizeMb  = round(($t['Data_length']+$t['Index_length'])/1024/1024,3);
                    $sizeKb  = round(($t['Data_length']+$t['Index_length'])/1024,1);
                    $pct     = $totalSize > 0 ? min(100, ($t['Data_length']+$t['Index_length'])/$totalSize*100) : 0;
                    $color   = $sizeMb > 5 ? 'var(--red)' : ($sizeMb > 1 ? 'var(--orange)' : 'var(--cyan)');
                ?>
                <tr>
                    <td class="adm-code" style="color:var(--cyan);font-weight:700;"><?php echo htmlspecialchars($t['Name']); ?></td>
                    <td style="text-align:center;"><span class="adm-badge gray"><?php echo number_format((int)$t['Rows']); ?></span></td>
                    <td style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:<?php echo $color; ?>;">
                        <?php echo $sizeKb < 1024 ? "$sizeKb KB" : "$sizeMb MB"; ?>
                    </td>
                    <td style="width:200px;">
                        <div class="adm-progress">
                            <div class="adm-progress-fill" style="width:<?php echo number_format($pct,1); ?>%;background:<?php echo $color; ?>;"></div>
                        </div>
                    </td>
                    <td style="color:var(--text-muted);font-size:0.78rem;"><?php echo htmlspecialchars($t['Collation']??'—'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
