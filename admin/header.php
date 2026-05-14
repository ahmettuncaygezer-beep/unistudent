<?php
require_once 'auth_check.php';
check_login();

$currentPage = basename($_SERVER['PHP_SELF']);
$pageIcons = [
    'index.php'               => 'fa-solid fa-gauge-high',
    'analytics.php'           => 'fa-solid fa-chart-column',
    'users.php'               => 'fa-solid fa-users',
    'transactions.php'        => 'fa-solid fa-arrows-left-right-to-line',
    'blogs.php'               => 'fa-solid fa-newspaper',
    'blog_editor.php'         => 'fa-solid fa-pen-to-square',
    'cities.php'              => 'fa-solid fa-city',
    'subscriptions_admin.php' => 'fa-solid fa-rotate',
    'challenges_admin.php'    => 'fa-solid fa-trophy',
    'discounts_admin.php'     => 'fa-solid fa-ticket',
    'activity_log.php'        => 'fa-solid fa-scroll',
    'notifications_admin.php' => 'fa-solid fa-bell',
    'database.php'            => 'fa-solid fa-database',
    'settings.php'            => 'fa-solid fa-gear',
];
$currentIcon = $pageIcons[$currentPage] ?? 'fa-solid fa-circle';

// Unread notifications count (for badge)
$notifCount = 0;
try {
    require_once __DIR__ . '/api/database.php';
    $dbh = getDB();
    $notifCount = (int)$dbh->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
} catch(Exception $e) { $notifCount = 0; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($PAGE_TITLE) ? htmlspecialchars($PAGE_TITLE).' — ÜniBütçe Admin' : 'Yönetim Paneli — ÜniBütçe'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__.'/admin.css'); ?>">
    <style>
        body { background: var(--bg-base) !important; }

        /* ── Global Search ── */
        .adm-global-search {
            position: relative;
        }
        .adm-global-search input {
            width: 220px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            padding: 7px 36px 7px 12px;
            font-size: 0.82rem;
            font-family: inherit;
            outline: none;
            transition: var(--transition);
        }
        .adm-global-search input:focus {
            border-color: rgba(0,240,255,0.35);
            background: rgba(0,0,0,0.3);
            width: 280px;
        }
        .adm-global-search .gs-icon {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.85rem;
            pointer-events: none;
        }
        .adm-gs-results {
            position: absolute;
            top: calc(100% + 6px); right: 0;
            width: 340px;
            background: rgba(10,14,28,0.98);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            z-index: 999;
            overflow: hidden;
            display: none;
        }
        .adm-gs-results.show { display: block; }
        .adm-gs-section { padding: 8px 0; }
        .adm-gs-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--text-muted); padding: 4px 14px; }
        .adm-gs-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; cursor: pointer; transition: background 0.15s;
        }
        .adm-gs-item:hover { background: rgba(255,255,255,0.05); }
        .adm-gs-item i { color: var(--cyan); font-size: 0.85rem; width: 16px; text-align: center; }
        .adm-gs-item-text { flex: 1; min-width: 0; }
        .adm-gs-item-name { font-size: 0.83rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .adm-gs-item-sub  { font-size: 0.72rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ── Notification bell ── */
        .adm-notif-bell {
            position: relative; cursor: pointer;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            border-radius: var(--radius-sm);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .adm-notif-bell:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
        .adm-notif-badge {
            position: absolute; top: -4px; right: -4px;
            width: 16px; height: 16px;
            background: var(--red);
            border-radius: 50%;
            font-size: 0.6rem; font-weight: 700; color: #fff;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--bg-base);
        }
        .adm-notif-badge:empty { display: none; }

        /* ── Sidebar toggle (collapse) ── */
        .adm-sidebar-toggle {
            position: absolute;
            right: -12px; top: 50%;
            transform: translateY(-50%);
            width: 24px; height: 24px;
            background: var(--bg-sidebar);
            border: 1px solid var(--border-accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.65rem; color: var(--cyan);
            z-index: 201; transition: var(--transition);
        }
        .adm-sidebar-toggle:hover { background: var(--cyan-dim); }

        /* Collapsed sidebar */
        .sidebar-collapsed .adm-sidebar { width: 64px; }
        .sidebar-collapsed .adm-main    { margin-left: 64px; }
        .sidebar-collapsed .adm-logo-text,
        .sidebar-collapsed .adm-nav-item span,
        .sidebar-collapsed .adm-nav-badge,
        .sidebar-collapsed .adm-nav-label,
        .sidebar-collapsed .adm-sidebar-footer a span { display: none; }
        .sidebar-collapsed .adm-logo-icon   { margin: 0 auto; }
        .sidebar-collapsed .adm-nav-item    { justify-content: center; padding: 10px 8px; }
        .sidebar-collapsed .adm-nav-item i  { width: auto; }
        .sidebar-collapsed .adm-sidebar-footer a { justify-content: center; padding: 10px 8px; }
        .sidebar-collapsed .adm-sidebar-logo { justify-content: center; padding: 0 8px; }
        .sidebar-collapsed .adm-sidebar-toggle i { transform: rotate(180deg); }
    </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="adm-sidebar" id="admSidebar">
    <a href="index.php" class="adm-sidebar-logo">
        <div class="adm-logo-icon">Ü</div>
        <div class="adm-logo-text">
            <strong>ÜniBütçe</strong>
            <span>Admin v2</span>
        </div>
    </a>
    <button class="adm-sidebar-toggle" id="sidebarToggle" title="Sidebar'ı küçült/genişlet">
        <i class="fa-solid fa-chevron-left"></i>
    </button>

    <div class="adm-sidebar-body">

        <span class="adm-nav-label">Genel</span>
        <a href="index.php"    class="adm-nav-item <?php echo $currentPage==='index.php'    ?'active':''; ?>"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
        <a href="analytics.php" class="adm-nav-item <?php echo $currentPage==='analytics.php'?'active':''; ?>"><i class="fa-solid fa-chart-column"></i><span>Analitik</span><span class="adm-nav-badge new">Yeni</span></a>

        <span class="adm-nav-label">Kullanıcılar & Veriler</span>
        <a href="users.php"        class="adm-nav-item <?php echo $currentPage==='users.php'    ?'active':''; ?>"><i class="fa-solid fa-users"></i><span>Kullanıcılar</span></a>
        <a href="transactions.php" class="adm-nav-item <?php echo $currentPage==='transactions.php'?'active':''; ?>"><i class="fa-solid fa-arrows-left-right-to-line"></i><span>İşlemler</span></a>
        <a href="subscriptions_admin.php" class="adm-nav-item <?php echo $currentPage==='subscriptions_admin.php'?'active':''; ?>"><i class="fa-solid fa-rotate"></i><span>Abonelikler</span></a>

        <span class="adm-nav-label">İçerik</span>
        <a href="blogs.php"        class="adm-nav-item <?php echo in_array($currentPage,['blogs.php','blog_editor.php'])?'active':''; ?>"><i class="fa-solid fa-newspaper"></i><span>Blog Yazıları</span></a>
        <a href="cities.php"       class="adm-nav-item <?php echo $currentPage==='cities.php'?'active':''; ?>"><i class="fa-solid fa-city"></i><span>Şehirler</span></a>

        <span class="adm-nav-label">Özellikler</span>
        <a href="challenges_admin.php" class="adm-nav-item <?php echo $currentPage==='challenges_admin.php'?'active':''; ?>"><i class="fa-solid fa-trophy"></i><span>Challenge'lar</span></a>
        <a href="discounts_admin.php"  class="adm-nav-item <?php echo $currentPage==='discounts_admin.php'?'active':''; ?>"><i class="fa-solid fa-ticket"></i><span>İndirimler</span></a>
        <a href="notifications_admin.php" class="adm-nav-item <?php echo $currentPage==='notifications_admin.php'?'active':''; ?>"><i class="fa-solid fa-bell"></i><span>Bildirimler</span><?php if($notifCount>0): ?><span class="adm-nav-badge" style="background:var(--red-dim);color:var(--red);"><?php echo min($notifCount,99); ?></span><?php endif; ?></a>

        <span class="adm-nav-label">Sistem</span>
        <a href="blog_editor.php"  class="adm-nav-item"><i class="fa-solid fa-robot"></i><span>AI Blog Studio</span><span class="adm-nav-badge ai">AI</span></a>
        <a href="activity_log.php" class="adm-nav-item <?php echo $currentPage==='activity_log.php'?'active':''; ?>"><i class="fa-solid fa-scroll"></i><span>Aktivite Logu</span></a>
        <a href="database.php"     class="adm-nav-item <?php echo $currentPage==='database.php'?'active':''; ?>"><i class="fa-solid fa-database"></i><span>Veritabanı</span></a>
        <a href="settings.php"     class="adm-nav-item <?php echo $currentPage==='settings.php'?'active':''; ?>"><i class="fa-solid fa-gear"></i><span>Ayarlar</span></a>

    </div>

    <div class="adm-sidebar-footer">
        <a href="../index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>Siteyi Görüntüle</span></a>
        <a href="logout.php" style="margin-top:4px;"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Çıkış Yap</span></a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="adm-main">

    <header class="adm-topbar">
        <div class="adm-topbar-left">
            <div class="adm-page-title">
                <i class="<?php echo $currentIcon; ?>"></i>
                <?php echo isset($PAGE_TITLE) ? htmlspecialchars($PAGE_TITLE) : 'Dashboard'; ?>
            </div>
        </div>
        <div class="adm-topbar-right">
            <span class="adm-topbar-time" id="admClock"></span>

            <!-- Global Search -->
            <div class="adm-global-search" id="globalSearchWrap">
                <input type="text" id="globalSearchInput" placeholder="Ara... (Ctrl+K)">
                <i class="fa-solid fa-magnifying-glass gs-icon"></i>
                <div class="adm-gs-results" id="gsResults"></div>
            </div>

            <!-- Notification Bell -->
            <div class="adm-notif-bell" id="notifBell" title="Bildirimler">
                <i class="fa-solid fa-bell" style="font-size:0.9rem;"></i>
                <?php if($notifCount>0): ?>
                <div class="adm-notif-badge"><?php echo min($notifCount,9); ?></div>
                <?php endif; ?>
            </div>

            <!-- View site -->
            <a href="../index.php" target="_blank" class="adm-btn-view-site">
                <i class="fa-solid fa-globe"></i> Siteyi Gör
            </a>

            <!-- Admin avatar with dropdown -->
            <div class="adm-topbar-avatar" title="Admin Profili">A</div>
        </div>
    </header>

    <div class="adm-content">
<!-- PAGE CONTENT INSERTED HERE -->

<script>
/* ── Sidebar Toggle ── */
(function(){
    const btn = document.getElementById('sidebarToggle');
    const body= document.body;
    const stored = localStorage.getItem('admSidebarCollapsed');
    if(stored==='1') body.classList.add('sidebar-collapsed');
    btn.addEventListener('click',()=>{
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('admSidebarCollapsed', body.classList.contains('sidebar-collapsed')?'1':'0');
    });
})();

/* ── Global Search ── */
(function(){
    const input  = document.getElementById('globalSearchInput');
    const results= document.getElementById('gsResults');
    let searchTimer;

    // Ctrl+K shortcut
    document.addEventListener('keydown', e => {
        if((e.ctrlKey || e.metaKey) && e.key==='k') { e.preventDefault(); input.focus(); }
        if(e.key==='Escape') { results.classList.remove('show'); input.blur(); }
    });

    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = input.value.trim();
        if(q.length < 2) { results.classList.remove('show'); return; }
        searchTimer = setTimeout(() => performSearch(q), 300);
    });
    input.addEventListener('focus', () => { if(input.value.trim().length>=2) results.classList.add('show'); });
    document.addEventListener('click', e => { if(!document.getElementById('globalSearchWrap').contains(e.target)) results.classList.remove('show'); });

    async function performSearch(q) {
        results.innerHTML = '<div style="padding:12px 14px;color:var(--text-muted);font-size:0.82rem;"><i class="fa-solid fa-spinner fa-spin"></i> Aranıyor...</div>';
        results.classList.add('show');

        const [usersRes, blogsRes] = await Promise.all([
            fetch(`api/admin_users.php?action=list&search=${encodeURIComponent(q)}&limit=5&offset=0`).then(r=>r.json()).catch(()=>({users:[]})),
            fetch(`api/admin_analytics.php?action=dummy`).catch(()=>null),
        ]);

        const users = (usersRes.users || []);
        const pages = [
            {name:'Dashboard',url:'index.php',icon:'fa-gauge-high'},
            {name:'Analitik',url:'analytics.php',icon:'fa-chart-column'},
            {name:'Kullanıcılar',url:'users.php',icon:'fa-users'},
            {name:'İşlemler',url:'transactions.php',icon:'fa-arrows-left-right-to-line'},
            {name:'Blog Yazıları',url:'blogs.php',icon:'fa-newspaper'},
            {name:'Şehirler',url:'cities.php',icon:'fa-city'},
            {name:'Challenge\'lar',url:'challenges_admin.php',icon:'fa-trophy'},
            {name:'İndirimler',url:'discounts_admin.php',icon:'fa-ticket'},
            {name:'Bildirimler',url:'notifications_admin.php',icon:'fa-bell'},
            {name:'Veritabanı',url:'database.php',icon:'fa-database'},
            {name:'Ayarlar',url:'settings.php',icon:'fa-gear'},
        ].filter(p=>p.name.toLowerCase().includes(q.toLowerCase()));

        let html = '';
        if(pages.length) {
            html += `<div class="adm-gs-section"><div class="adm-gs-label">Sayfalar</div>${pages.slice(0,4).map(p=>`
                <a href="${p.url}" class="adm-gs-item" style="text-decoration:none;">
                    <i class="fa-solid ${p.icon}"></i>
                    <div class="adm-gs-item-text"><div class="adm-gs-item-name">${p.name}</div></div>
                    <i class="fa-solid fa-arrow-right" style="font-size:0.7rem;color:var(--text-muted);"></i>
                </a>`).join('')}</div>`;
        }
        if(users.length) {
            html += `<div class="adm-gs-section"><div class="adm-gs-label">Kullanıcılar</div>${users.map(u=>`
                <div class="adm-gs-item" onclick="window.location='users.php'">
                    <i class="fa-solid fa-user"></i>
                    <div class="adm-gs-item-text">
                        <div class="adm-gs-item-name">${esc(u.full_name)}</div>
                        <div class="adm-gs-item-sub">${esc(u.email)}</div>
                    </div>
                    <span class="adm-badge gray" style="font-size:0.65rem;">${{user:'Standart',verified:'Doğrulanmış',admin:'Yönetici'}[u.role]||u.role}</span>
                </div>`).join('')}</div>`;
        }
        if(!html) html = '<div style="padding:16px 14px;color:var(--text-muted);font-size:0.82rem;text-align:center;"><i class="fa-solid fa-magnifying-glass" style="opacity:0.3;display:block;font-size:1.5rem;margin-bottom:8px;"></i>Sonuç bulunamadı</div>';
        results.innerHTML = html;
    }

    function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
})();

/* ── Notification bell click ── */
document.getElementById('notifBell').addEventListener('click', ()=>{ window.location.href='notifications_admin.php'; });
</script>
