            </div> <!-- /.tool-page -->
        </div> <!-- /.profile-overhaul-container -->
    </main>

    <footer class="footer" style="margin-top:60px;">
        <div class="container">
            <div class="footer-bottom">
                <p>© <?= date('Y') ?> ÜniBütçe — Öğrenciler için, öğrenciler tarafından ❤️ ile yapıldı.</p>
                <p style="margin-top:8px; opacity:.6; font-size:.85rem;">
                    <a href="<?= $__appUrl ?? '' ?>/roadmap.php">Yol Haritası</a> ·
                    <a href="<?= $__appUrl ?? '' ?>/changelog.php">Changelog</a> ·
                    <a href="<?= $__appUrl ?? '' ?>/status.php">Durum</a> ·
                    <a href="<?= $__appUrl ?? '' ?>/privacy.php">Gizlilik</a> ·
                    <a href="<?= $__appUrl ?? '' ?>/kvkk.php">KVKK</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Cookie Consent Banner (KVKK / e-Privacy) -->
    <div id="ubCookieBanner" style="display:none; position:fixed; bottom:20px; left:20px; right:20px; max-width:720px; margin:0 auto; padding:18px 22px; background:rgba(12,14,26,.95); border:1px solid rgba(125,92,255,.3); border-radius:14px; backdrop-filter:blur(16px); z-index:10000; box-shadow:0 12px 40px rgba(0,0,0,.5);">
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <b style="color:#fff;">🍪 Çerez tercihlerin</b>
                <p style="margin:6px 0 0; opacity:.75; font-size:.88rem; color:#fff;">
                    Oturum için zorunlu çerezler kullanıyoruz. Opsiyonel analitik çerezlere izin verir misin?
                    <a href="<?= $__appUrl ?? '' ?>/kvkk.php" style="color:#0088ff;">KVKK metni</a>
                </p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button onclick="ubCookieConsent(false)" style="padding:10px 18px; border-radius:8px; background:transparent; color:rgba(255,255,255,.85); border:1px solid rgba(255,255,255,.2); cursor:pointer; font-weight:600;">Sadece zorunlu</button>
                <button onclick="ubCookieConsent(true)" style="padding:10px 18px; border-radius:8px; background:linear-gradient(135deg,#0088ff,#7d5cff); color:#fff; border:0; cursor:pointer; font-weight:600;">Hepsini kabul et</button>
            </div>
        </div>
    </div>
    <script>
    (function(){
        if (localStorage.getItem('ub_cookie_consent')) return;
        document.getElementById('ubCookieBanner').style.display = 'block';
    })();
    function ubCookieConsent(analytics) {
        localStorage.setItem('ub_cookie_consent', JSON.stringify({necessary:true, analytics:!!analytics, marketing:false, at: Date.now()}));
        document.getElementById('ubCookieBanner').style.display = 'none';
        // Sunucuya anonim kayıt (log amaçlı - isteğe bağlı)
        try {
            const fd = new FormData();
            fd.append('analytics', analytics ? 1 : 0);
            fetch('api/cookie_consent.php', {method:'POST', body:fd, credentials:'same-origin'}).catch(()=>{});
        } catch(e){}
    }
    </script>
</body>
</html>
