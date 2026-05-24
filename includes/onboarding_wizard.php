<?php
/**
 * ÜniBütçe — Onboarding Wizard (V6.0)
 * İlk giriş yapan kullanıcılar için 3 adımlı hoş geldin ekranı
 */
$showOnboarding = false;
try {
    $obStmt = $pdo->prepare("SELECT onboarding_done FROM users WHERE id = ?");
    $obStmt->execute([$_SESSION['user_id']]);
    $obRow = $obStmt->fetch();
    if ($obRow && !(int)$obRow['onboarding_done']) {
        $showOnboarding = true;
    }
} catch (Exception $e) { /* tablo yoksa atla */ }
?>

<?php if ($showOnboarding): ?>
<div class="onboarding-overlay" id="onboardingOverlay">
    <div class="onboarding-modal">
        <div class="onboarding-progress">
            <div class="ob-step active" data-step="1">1</div>
            <div class="ob-line"></div>
            <div class="ob-step" data-step="2">2</div>
            <div class="ob-line"></div>
            <div class="ob-step" data-step="3">3</div>
        </div>

        <!-- Step 1 -->
        <div class="ob-page active" id="obPage1">
            <div class="ob-emoji">🎓</div>
            <h2>Hoş Geldin, Kaptan!</h2>
            <p>ÜniBütçe ile finansal kontrolünü ele al. Önce üniversite şehrini seçelim.</p>
            <select id="obCitySelect" class="ob-input">
                <option value="">Şehir Seç...</option>
                <option>İstanbul</option><option>Ankara</option><option>İzmir</option>
                <option>Eskişehir</option><option>Antalya</option><option>Bursa</option>
                <option>Kocaeli</option><option>Konya</option><option>Trabzon</option>
                <option>Diğer</option>
            </select>
            <button class="ob-btn" onclick="obNext(2)">Devam →</button>
        </div>

        <!-- Step 2 -->
        <div class="ob-page" id="obPage2">
            <div class="ob-emoji">💰</div>
            <h2>Gelirini Belirle</h2>
            <p>Aylık toplam gelirini gir. KYK, burs, aile desteği dahil.</p>
            <input type="number" id="obIncomeInput" class="ob-input" placeholder="Aylık gelir (₺)" min="0">
            <button class="ob-btn" onclick="obNext(3)">Devam →</button>
        </div>

        <!-- Step 3 -->
        <div class="ob-page" id="obPage3">
            <div class="ob-emoji">🎯</div>
            <h2>İlk Hedefin</h2>
            <p>Bu ay ne kadar tasarruf etmek istiyorsun?</p>
            <input type="number" id="obSavingsInput" class="ob-input" placeholder="Tasarruf hedefi (₺)" min="0">
            <button class="ob-btn ob-btn-final" onclick="obFinish()">🚀 Başlayalım!</button>
        </div>
    </div>
</div>

<style>
.onboarding-overlay {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(5,5,10,0.92);
    backdrop-filter: blur(12px);
    display: flex; align-items: center; justify-content: center;
    animation: obFadeIn 0.4s ease-out;
}
@keyframes obFadeIn { from { opacity: 0; } to { opacity: 1; } }

.onboarding-modal {
    background: var(--bg-card);
    border: 1px solid var(--accent-cyan);
    border-radius: 16px;
    padding: 40px;
    width: 440px;
    max-width: 90vw;
    text-align: center;
    box-shadow: 0 0 60px rgba(0,240,255,0.15);
    position: relative;
}
.onboarding-progress {
    display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 30px;
}
.ob-step {
    width: 32px; height: 32px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-mono); font-size: 0.8rem; font-weight: 700;
    color: var(--text-muted); transition: all 0.3s;
}
.ob-step.active {
    border-color: var(--accent-cyan);
    color: var(--accent-cyan);
    box-shadow: 0 0 12px rgba(0,240,255,0.3);
}
.ob-step.done { background: var(--accent-cyan); color: #000; border-color: var(--accent-cyan); }
.ob-line { width: 40px; height: 2px; background: rgba(255,255,255,0.1); }

.ob-page { display: none; animation: obSlide 0.3s ease-out; }
.ob-page.active { display: block; }
@keyframes obSlide { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

.ob-emoji { font-size: 3rem; margin-bottom: 16px; }
.ob-page h2 { font-family: var(--font-display); font-size: 1.4rem; margin-bottom: 8px; }
.ob-page p { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px; }
.ob-input {
    width: 100%; padding: 12px 16px;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px; color: var(--text-primary); font-size: 1rem;
    outline: none; margin-bottom: 16px; transition: border 0.2s;
}
.ob-input:focus { border-color: var(--accent-cyan); }
.ob-btn {
    width: 100%; padding: 14px; border: none; border-radius: 8px;
    background: var(--accent-cyan); color: #000; font-weight: 700;
    font-family: var(--font-display); font-size: 1rem; cursor: pointer;
    transition: all 0.2s;
}
.ob-btn:hover { box-shadow: 0 0 20px rgba(0,240,255,0.4); transform: translateY(-1px); }
.ob-btn-final { background: var(--accent-purple); color: #fff; }
.ob-btn-final:hover { box-shadow: 0 0 20px rgba(112,0,255,0.4); }
</style>

<script>
function obNext(step) {
    document.querySelectorAll('.ob-page').forEach(p => p.classList.remove('active'));
    document.getElementById('obPage' + step).classList.add('active');
    document.querySelectorAll('.ob-step').forEach(s => {
        const sNum = parseInt(s.dataset.step);
        s.classList.remove('active', 'done');
        if (sNum < step) s.classList.add('done');
        if (sNum === step) s.classList.add('active');
    });
}

async function obFinish() {
    const fd = new FormData();
    fd.append('action', 'complete_onboarding');
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fd.append('city', document.getElementById('obCitySelect')?.value || '');
    fd.append('income', document.getElementById('obIncomeInput')?.value || '0');
    fd.append('savings_goal', document.getElementById('obSavingsInput')?.value || '0');

    try {
        await fetch('api/auth_handler.php', { method: 'POST', body: fd });
    } catch(e) {}

    // Mark done locally so it doesn't show again
    const overlay = document.getElementById('onboardingOverlay');
    overlay.style.animation = 'obFadeIn 0.3s ease-out reverse';
    setTimeout(() => overlay.remove(), 300);
}
</script>
<?php endif; ?>
