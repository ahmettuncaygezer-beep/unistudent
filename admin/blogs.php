<?php
$PAGE_TITLE = 'Blog Yönetimi';
require_once 'header.php';
require_once 'api/blog_manager.php';

$bm    = new BlogManager();
$blogs = $bm->getBlogs();
?>

<div class="adm-section-header">
    <div>
        <div class="adm-section-title">Blog Yazıları</div>
        <div class="adm-section-desc">Toplam <strong style="color:var(--cyan);"><?php echo count($blogs); ?></strong> yazı</div>
    </div>
    <a href="blog_editor.php" class="adm-btn adm-btn-primary"><i class="fa-solid fa-plus"></i> Yeni Yazı (AI Studio)</a>
</div>

<div class="adm-table-wrap">
    <div class="adm-table-toolbar">
        <div class="adm-search-wrap" style="max-width:300px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="blogSearch" placeholder="Başlık ara...">
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="adm-table" id="blogTable">
            <thead>
                <tr>
                    <th style="width:70px;">Görsel</th>
                    <th>Başlık</th>
                    <th>Dosya</th>
                    <th style="text-align:center;">Durum</th>
                    <th>Son Güncelleme</th>
                    <th style="text-align:right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($blogs)): ?>
                <tr><td colspan="6">
                    <div class="adm-empty"><i class="fa-solid fa-newspaper"></i><p>Henüz blog yazısı yok. <a href="blog_editor.php" style="color:var(--cyan);">İlk yazıyı oluştur</a></p></div>
                </td></tr>
                <?php else: ?>
                <?php foreach($blogs as $blog): ?>
                <tr class="blog-row" data-title="<?php echo strtolower(htmlspecialchars($blog['title'])); ?>">
                    <td>
                        <?php if(!empty($blog['cover'])): ?>
                            <img src="<?php echo htmlspecialchars($blog['cover']); ?>" style="width:56px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                        <?php else: ?>
                            <div style="width:56px;height:40px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-image" style="opacity:0.3;"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600;max-width:300px;"><?php echo htmlspecialchars($blog['title']); ?></td>
                    <td class="adm-code" style="color:var(--text-muted);font-size:0.77rem;"><?php echo htmlspecialchars($blog['filename']); ?></td>
                    <td style="text-align:center;">
                        <?php
                        $status = $blog['status'] ?? 'published';
                        $scls   = $status === 'published' ? 'green' : 'orange';
                        $slbl   = $status === 'published' ? 'Yayında' : 'Taslak';
                        echo "<span class='adm-badge $scls'>$slbl</span>";
                        ?>
                    </td>
                    <td style="color:var(--text-muted);font-size:0.8rem;"><?php echo date('d.m.Y H:i', $blog['modified']); ?></td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:6px;">
                            <a href="../blog/<?php echo urlencode($blog['filename']); ?>" target="_blank" class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" title="Önizle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="blog_editor.php?file=<?php echo urlencode($blog['filename']); ?>" class="adm-btn adm-btn-primary adm-btn-icon adm-btn-sm" title="Düzenle">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" onclick="deleteBlog('<?php echo htmlspecialchars($blog['filename'],ENT_QUOTES); ?>')" title="Sil">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('blogSearch').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.blog-row').forEach(r => {
        r.style.display = r.dataset.title.includes(q) ? '' : 'none';
    });
});

function deleteBlog(filename) {
    admConfirm('Blog Yazısını Sil', `"${filename}" isimli yazıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!`, async () => {
        const data = await admFetch('api/save_blog.php', {action:'delete', filename});
        if(data.success) {
            admToast('success','Yazı Silindi','Blog yazısı başarıyla silindi.');
            setTimeout(()=>location.reload(), 1200);
        } else {
            admToast('error','Hata',data.message||'Silme başarısız.');
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
