<?php
$__title    = 'Finansal IQ Testi — Finansal Okuryazarlık Seviyeni Ölç';
$__desc     = '20 soruluk finansal okuryazarlık testi ile seviyeni ölç. Bütçe, yatırım, vergi ve tasarruf konularında ne kadar bilgilisin?';
$__keywords = 'finansal okuryazarlık testi, finansal IQ, para testi, bütçe bilgi testi, finans quiz, öğrenci finans';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/finansal-iq.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "Quiz",
    "name" => "Finansal IQ Testi",
    "description" => $__desc,
    "url" => $__canonical,
    "educationalLevel" => "University",
    "about" => ["@type" => "Thing", "name" => "Finansal Okuryazarlık"]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$__allowDemoLogin = true; // Public tool page — allow demo auto-login
require __DIR__ . '/../includes/seo_tools_head.php';
?>

<div class="tool-breadcrumb">
    <a href="../index.php">Ana Sayfa</a><span class="separator">›</span>
    <a href="index.php">Araçlar</a><span class="separator">›</span>
    <span class="current">Finansal IQ Testi</span>
</div>

<div class="tool-hero">
    <div class="tool-badge">🧠 Quiz</div>
    <h1 class="tool-title">Finansal IQ <span class="tg">Testi</span></h1>
    <p class="tool-subtitle">20 soruda finansal okuryazarlık seviyeni ölç. Sonucu arkadaşlarınla paylaş!</p>
</div>

<!-- Quiz Container -->
<div id="quizContainer">
    <!-- Start Screen -->
    <div id="startScreen" class="tool-card" style="text-align:center; padding:48px 32px;">
        <div style="font-size:4rem; margin-bottom:16px;">🧠</div>
        <h2 style="margin:0 0 12px; font-size:1.5rem;">Finansal Okuryazarlık Testi</h2>
        <p style="color:rgba(255,255,255,.6); max-width:500px; margin:0 auto 24px; line-height:1.7;">
            20 çoktan seçmeli soru ile bütçe, yatırım, vergi ve tasarruf konularındaki bilgini test et.
            Sonunda sınıfının yüzde kaçından iyi olduğunu öğren!
        </p>
        <div class="tool-stats-grid" style="max-width:400px; margin:0 auto 32px;">
            <div class="tool-stat"><div class="tool-stat-value clr-cyan">20</div><div class="tool-stat-label">Soru</div></div>
            <div class="tool-stat"><div class="tool-stat-value clr-purple">~5 dk</div><div class="tool-stat-label">Süre</div></div>
        </div>
        <button class="tool-btn tool-btn-primary" onclick="startQuiz()" style="font-size:1.1rem; padding:16px 48px;">
            🚀 Teste Başla
        </button>
    </div>

    <!-- Question Screen -->
    <div id="questionScreen" style="display:none;">
        <div class="tool-card">
            <!-- Progress Bar -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span style="font-size:0.85rem; color:rgba(255,255,255,.5); font-weight:600;" id="progressText">Soru 1/20</span>
                <span style="font-size:0.85rem; color:#00d4ff; font-weight:700;" id="scoreText">Doğru: 0</span>
            </div>
            <div style="height:6px; background:rgba(255,255,255,.08); border-radius:3px; overflow:hidden; margin-bottom:28px;">
                <div id="progressBar" style="height:100%; background:linear-gradient(90deg,#0088ff,#00d4ff); border-radius:3px; transition:width 0.4s ease; width:5%;"></div>
            </div>

            <!-- Question -->
            <h3 id="questionText" style="font-size:1.15rem; line-height:1.6; margin:0 0 24px;"></h3>

            <!-- Options -->
            <div id="optionsContainer" style="display:flex; flex-direction:column; gap:10px;"></div>

            <!-- Next Button (hidden until answered) -->
            <button id="nextBtn" class="tool-btn tool-btn-primary tool-btn-block" onclick="nextQuestion()" style="margin-top:20px; display:none;">
                Sonraki Soru →
            </button>
        </div>
    </div>

    <!-- Result Screen -->
    <div id="resultScreen" style="display:none;">
        <div class="tool-result">
            <div class="tool-result-label">Finansal IQ Skorun</div>
            <div class="tool-result-value" id="finalScore">0</div>
            <div class="tool-result-sub" id="finalSub"></div>
        </div>

        <div class="tool-stats-grid">
            <div class="tool-stat"><div class="tool-stat-value clr-green" id="correctCount">0</div><div class="tool-stat-label">Doğru</div></div>
            <div class="tool-stat"><div class="tool-stat-value clr-red" id="wrongCount">0</div><div class="tool-stat-label">Yanlış</div></div>
            <div class="tool-stat"><div class="tool-stat-value clr-cyan" id="percentile">%0</div><div class="tool-stat-label">Yüzdelik Dilim</div></div>
            <div class="tool-stat"><div class="tool-stat-value" id="levelText">—</div><div class="tool-stat-label">Seviye</div></div>
        </div>

        <!-- Share Buttons -->
        <div class="tool-card" style="text-align:center; padding:32px;">
            <h3 style="margin:0 0 16px; font-size:1.1rem;">📣 Sonucunu Paylaş</h3>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <button class="tool-btn tool-btn-primary" onclick="shareResult('twitter')" style="padding:12px 24px;">
                    𝕏 Twitter
                </button>
                <button class="tool-btn tool-btn-secondary" onclick="shareResult('whatsapp')" style="padding:12px 24px;">
                    💬 WhatsApp
                </button>
                <button class="tool-btn tool-btn-secondary" onclick="shareResult('copy')" style="padding:12px 24px;">
                    📋 Linki Kopyala
                </button>
            </div>
        </div>

        <button class="tool-btn tool-btn-secondary tool-btn-block" onclick="startQuiz()" style="margin-top:16px;">
            🔄 Tekrar Dene
        </button>
    </div>
</div>

<script>
const QUESTIONS = [
    { q: "Bileşik faiz ile basit faiz arasındaki temel fark nedir?", o: ["Bileşik faiz sadece anaparaya uygulanır","Bileşik faiz, faiz üzerine de faiz hesaplar","İkisi aynıdır","Basit faiz daha yüksek getiri sağlar"], a: 1 },
    { q: "Enflasyon %20 ise ve maaşın %15 artarsa, gerçek gelirin ne olur?", o: ["Artar","Aynı kalır","Azalır","Hesaplanamaz"], a: 2 },
    { q: "'72 Kuralı' neyi hesaplamak için kullanılır?", o: ["Vergini","Paranın kaç yılda ikiye katlanacağını","Enflasyon oranını","Faiz oranını"], a: 1 },
    { q: "Hisse senedi ve tahvil arasındaki temel fark nedir?", o: ["İkisi de aynıdır","Hisse ortaklık, tahvil borçtur","Tahvil daha risklidir","Hisse sabit getiri sağlar"], a: 1 },
    { q: "Acil durum fonu olarak kaç aylık gider biriktirmeniz önerilir?", o: ["1 ay","3-6 ay","12 ay","24 ay"], a: 1 },
    { q: "KYK kredisi geri ödemesinde uygulanan endeksleme nedir?", o: ["TÜFE","Yİ-ÜFE","Dolar kuru","Sabit faiz"], a: 1 },
    { q: "Kredi kartı borcunun asgari tutarını ödersen ne olur?", o: ["Borç kapanır","Kalan borca faiz işler","Kredi notu artar","Hiçbir şey olmaz"], a: 1 },
    { q: "'Portföy çeşitlendirmesi' ne anlama gelir?", o: ["Tek hisseye yatırım","Tüm parayı altına koyma","Farklı varlık sınıflarına yayarak risk azaltma","Borç alıp yatırım yapma"], a: 2 },
    { q: "Gelir vergisi dilimi arttığında ne olur?", o: ["Tüm gelirine yüksek vergi uygulanır","Sadece üst dilime giren kısma yüksek vergi uygulanır","Vergi iadesi alırsın","Maaşın düşer"], a: 1 },
    { q: "SGK işçi payı brüt maaşın yüzde kaçıdır?", o: ["%7","%14","%20","%25"], a: 1 },
    { q: "100.000₺'nin yıllık %10 bileşik faizle 3 yıl sonraki değeri nedir?", o: ["130.000₺","133.100₺","131.000₺","110.000₺"], a: 1 },
    { q: "'Likidite' kavramı ne anlama gelir?", o: ["Kârlılık oranı","Bir varlığın nakde çevrilebilme hızı","Borç ödeme kapasitesi","Risk seviyesi"], a: 1 },
    { q: "Öğrenci kredisi çekerken en çok dikkat edilmesi gereken nedir?", o: ["Geri ödeme koşulları ve faiz oranı","Bankanın şube sayısı","Kartın rengi","Hediye puan miktarı"], a: 0 },
    { q: "BIST 100 endeksi neyi gösterir?", o: ["Altın fiyatını","Borsa İstanbul'daki en büyük 100 şirketin performansını","Döviz kurunu","Enflasyon oranını"], a: 1 },
    { q: "Damga vergisi oranı yaklaşık ne kadardır?", o: ["%0.759","%5","%18","%1"], a: 0 },
    { q: "Tasarruf yaparken '50/30/20 kuralı' neyi ifade eder?", o: ["Geliri 3 farklı bankaya bölme","İhtiyaçlar %50, istekler %30, tasarruf %20","Her ay %50 biriktirme","Sadece zenginler için geçerli"], a: 1 },
    { q: "Kripto para yatırımında 'volatilite' ne demektir?", o: ["Sabit getiri","Fiyatın çok fazla dalgalanması","Vergiden muafiyet","Garantili kâr"], a: 1 },
    { q: "KDV oranı temel gıda maddeleri için genellikle ne kadardır?", o: ["%1","%10","%18","%20"], a: 0 },
    { q: "Ev kirası gelirinin yüzde kaçını geçmemelidir?", o: ["%60","%50","%30","%10"], a: 2 },
    { q: "Finansal özgürlük ne demektir?", o: ["Çok para kazanmak","Pasif gelirin yaşam giderlerini karşılaması","Hiç çalışmamak","Borçsuz olmak"], a: 1 }
];

let currentQ = 0, score = 0, answered = false, shuffledQuestions = [];

function shuffleArray(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function startQuiz() {
    currentQ = 0; score = 0; answered = false;
    shuffledQuestions = shuffleArray(QUESTIONS);
    document.getElementById('startScreen').style.display = 'none';
    document.getElementById('resultScreen').style.display = 'none';
    document.getElementById('questionScreen').style.display = 'block';
    showQuestion();
}

function showQuestion() {
    answered = false;
    const q = shuffledQuestions[currentQ];
    document.getElementById('progressText').textContent = `Soru ${currentQ + 1}/20`;
    document.getElementById('scoreText').textContent = `Doğru: ${score}`;
    document.getElementById('progressBar').style.width = ((currentQ + 1) / 20 * 100) + '%';
    document.getElementById('questionText').textContent = q.q;
    document.getElementById('nextBtn').style.display = 'none';

    const container = document.getElementById('optionsContainer');
    container.innerHTML = q.o.map((opt, i) => `
        <button class="quiz-option" onclick="selectAnswer(${i})" data-idx="${i}" style="
            display:block; width:100%; padding:16px 20px; border-radius:14px;
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1);
            color:#fff; font-size:0.95rem; text-align:left; cursor:pointer;
            transition:all 0.3s ease; font-family:inherit; line-height:1.5;
        ">${String.fromCharCode(65 + i)}) ${opt}</button>
    `).join('');
}

function selectAnswer(idx) {
    if (answered) return;
    answered = true;
    const q = shuffledQuestions[currentQ];
    const correct = q.a;
    const btns = document.querySelectorAll('.quiz-option');

    btns.forEach((btn, i) => {
        btn.style.cursor = 'default';
        if (i === correct) {
            btn.style.background = 'rgba(57,255,20,0.15)';
            btn.style.borderColor = '#39ff14';
            btn.style.color = '#39ff14';
        } else if (i === idx && idx !== correct) {
            btn.style.background = 'rgba(255,59,48,0.15)';
            btn.style.borderColor = '#ff3b30';
            btn.style.color = '#ff3b30';
        } else {
            btn.style.opacity = '0.4';
        }
    });

    if (idx === correct) score++;

    if (currentQ < 19) {
        document.getElementById('nextBtn').style.display = 'block';
        document.getElementById('nextBtn').textContent = 'Sonraki Soru →';
    } else {
        document.getElementById('nextBtn').style.display = 'block';
        document.getElementById('nextBtn').textContent = '📊 Sonuçları Gör';
    }
}

function nextQuestion() {
    currentQ++;
    if (currentQ >= 20) {
        showResults();
    } else {
        showQuestion();
    }
}

function showResults() {
    document.getElementById('questionScreen').style.display = 'none';
    document.getElementById('resultScreen').style.display = 'block';

    const pct = (score / 20 * 100);
    // Simulated percentile based on score
    const percentile = Math.min(99, Math.round(pct * 1.1 + Math.random() * 5));

    let level = '', levelColor = '';
    if (score >= 18) { level = '🏆 Finansal Uzman'; levelColor = '#00d4ff'; }
    else if (score >= 14) { level = '🌟 İleri Düzey'; levelColor = '#39ff14'; }
    else if (score >= 10) { level = '✅ Orta Düzey'; levelColor = '#ffd700'; }
    else if (score >= 6) { level = '⚠️ Başlangıç'; levelColor = '#ff9100'; }
    else { level = '📚 Öğrenmeye Devam'; levelColor = '#ff3b30'; }

    document.getElementById('finalScore').textContent = `${score}/20`;
    document.getElementById('finalSub').textContent = `Sınıfının %${percentile}'inden daha iyi skor aldın!`;
    document.getElementById('correctCount').textContent = score;
    document.getElementById('wrongCount').textContent = 20 - score;
    document.getElementById('percentile').textContent = `%${percentile}`;
    document.getElementById('percentile').style.color = levelColor;
    document.getElementById('levelText').textContent = level;
    document.getElementById('levelText').style.color = levelColor;
    document.getElementById('levelText').style.fontSize = '0.8rem';

    document.getElementById('resultScreen').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function shareResult(platform) {
    const text = `🧠 Finansal IQ Testim: ${score}/20! Sınıfımın %${Math.min(99, Math.round(score/20*100*1.1))}\'inden daha iyiyim! Sen de dene 👉`;
    const url = window.location.href;

    if (platform === 'twitter') {
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
    } else if (platform === 'whatsapp') {
        window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
    } else if (platform === 'copy') {
        navigator.clipboard.writeText(text + ' ' + url).then(() => {
            const btn = event.target;
            btn.textContent = '✅ Kopyalandı!';
            setTimeout(() => btn.textContent = '📋 Linki Kopyala', 2000);
        });
    }
}
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
