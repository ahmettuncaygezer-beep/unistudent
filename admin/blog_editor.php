<?php

$PAGE_TITLE = 'Blog Editörü';
require_once 'header.php';

require_once 'api/blog_manager.php';

$filename = $_GET['file'] ?? null;
$blog = null;
if ($filename) {
    $bm = new BlogManager();
    $blog = $bm->getBlogContent($filename);
}

$title = $blog ? $blog['title'] : '';
$slug = $filename ? $filename : '';
$description = $blog ? $blog['description'] : '';
$keywords = $blog ? $blog['keywords'] : '';
$content = $blog ? $blog['content'] : '';
$coverImage = $blog['cover_image'] ?? '';
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
/* Custom Quill Theme for Glassmorphism */
.ql-toolbar.ql-snow {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 0.5rem 0.5rem 0 0;
    background: rgba(255, 255, 255, 0.05);
}
.ql-container.ql-snow {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-top: none !important;
    border-radius: 0 0 0.5rem 0.5rem;
    background: rgba(0, 0, 0, 0.2);
    color: #dfe6e9; /* Light text */
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    height: 500px; /* Fixed height for scroll */
}
.ql-editor {
    min-height: 300px;
}
.ql-snow .ql-stroke {
    stroke: #b2bec3 !important;
}
.ql-snow .ql-fill {
    fill: #b2bec3 !important;
}
.ql-snow .ql-picker {
    color: #b2bec3 !important;
}
/* Toolbar active states */
.ql-snow .ql-picker-options {
    background-color: #2d2d3f !important; /* Dark dropdown */
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: white !important;
}

/* Editor Specific Styles to Match Frontend */
.ql-editor {
    font-family: 'Inter', sans-serif;
    line-height: 1.8;
    font-size: 1rem;
    color: #f0f0ff;
    padding: 2rem;
}

.ql-editor h2 {
    font-size: 1.5rem;
    color: #f0f0ff;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 0.5rem;
}

.ql-editor h3 {
    font-size: 1.25rem;
    color: #00e5ff; /* Accent Cyan */
    margin-top: 1.5rem;
}

/* Info Boxes */
.ql-editor .blog-info-box {
    padding: 1.5rem;
    margin: 1.5rem 0;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.03);
}

.ql-editor .blog-info-box.highlight {
    background: rgba(79, 140, 255, 0.1);
    border-color: rgba(79, 140, 255, 0.2);
}

.ql-editor .blog-info-box.warning {
    background: rgba(255, 145, 0, 0.1);
    border-color: rgba(255, 145, 0, 0.2);
}

.ql-editor .blog-info-box.tip {
    background: rgba(0, 230, 118, 0.1);
    border-color: rgba(0, 230, 118, 0.2);
}

/* Tables */
.ql-editor .blog-table-wrapper {
    overflow-x: auto;
    margin: 1.5rem 0;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
}

.ql-editor table.blog-table {
    width: 100%;
    border-collapse: collapse;
}

.ql-editor table.blog-table th {
    background: rgba(0,0,0,0.3);
    padding: 1rem;
    text-align: left;
    color: #a29bfe;
}

.ql-editor table.blog-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.ql-editor .highlight-value {
    color: #00e5ff;
    font-weight: bold;
}

/* TOC */
.ql-editor .blog-toc {
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.ql-editor .blog-toc-title {
    font-weight: bold;
    color: #a29bfe;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

/* AI V2 Enhanced Styles */
.ql-editor .toc-box {
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.ql-editor .toc-box h2 {
    font-weight: bold;
    color: #a29bfe;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    border-bottom: none !important;
}

.ql-editor .toc-box ul {
    list-style: none;
    padding-left: 0;
}

.ql-editor .toc-box ul li a {
    color: #00e5ff;
    text-decoration: none;
}

.ql-editor .info-box {
    padding: 1.5rem;
    margin: 1.5rem 0;
    border-radius: 8px;
    border-left: 4px solid #00e5ff;
    background: rgba(255,255,255,0.03);
}

.ql-editor .info-box strong {
    color: #00e5ff;
    display: block;
    margin-bottom: 0.5rem;
}

.ql-editor .snippet-box {
    background: rgba(0, 229, 255, 0.05);
    border: 1px dashed rgba(0, 229, 255, 0.3);
    padding: 1.5rem;
    border-radius: 12px;
    margin: 2rem 0;
}

.ql-editor .snippet-box p {
    margin: 0;
    font-style: italic;
    color: #e0faff;
}

.ql-editor .blog-cta {
    background: linear-gradient(135deg, rgba(9, 132, 227, 0.1), rgba(108, 92, 231, 0.1));
    border: 1px solid rgba(108, 92, 231, 0.2);
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    margin-top: 3rem;
}

.ql-editor .blog-cta h3 {
    margin-top: 0;
    color: white;
}

.ql-editor .blog-cta .btn {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: #6c5ce7;
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: bold;
    margin-top: 1rem;
}
.ql-snow .ql-tooltip {
    background-color: #2d2d3f !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: white !important;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3) !important;
}
.ql-snow .ql-tooltip input[type=text] {
    border: 1px solid rgba(255,255,255,0.2) !important;
    background: rgba(0,0,0,0.2) !important;
    color: white !important;
}

/* Modern Cover Input */
.cover-zone {
    border: 2px dashed rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.02);
    padding: 2rem;
    text-align: center;
    border-radius: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.cover-zone:hover {
    border-color: var(--primary);
    background: rgba(108, 92, 231, 0.05);
}
.cover-preview {
    width: 100%;
    margin-top: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    max-height: 200px;
    object-fit: cover;
    display: none;
}
.cover-preview.active {
    display: block;
}
</style>

<div class="glass-card" style="padding: 2rem;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin:0; font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ph ph-article" style="color: var(--primary);"></i>
            <?php echo $filename ? 'Yazıyı Düzenle' : 'Yeni Blog Yazısı'; ?>
        </h3>
        <a href="blogs.php" class="action-btn" style="background: rgba(255,255,255,0.05); text-decoration: none;">Vazgeç</a>
    </div>

    <form id="blogForm">
        <input type="hidden" name="filename" id="slugInputValue" value="<?php echo htmlspecialchars($slug); ?>">
        
        <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 2rem; align-items: start;">
            
            <!-- Main Content Column -->
            <div class="left-col">
                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.8rem; color: var(--text-secondary); font-size: 0.9rem;">Yazı Başlığı</label>
                    <input type="text" name="title" class="form-input" 
                        style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 0.8rem; font-size: 1.2rem; font-weight: 500;" 
                        placeholder="Örn: 2026 KYK Yurt Fiyatları"
                        required value="<?php echo htmlspecialchars($title); ?>" oninput="generateSlug(this.value)">
                </div>
                
                <div class="form-group" style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                        <label class="form-label" style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">İçerik</label>
                        <button type="button" onclick="openAIModal2()" style="padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); border: none; border-radius: 0.5rem; color: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="ph ph-sparkle"></i> AI ile İçerik Üret
                        </button>
                    </div>
                    
                    <!-- Quill Editor Container -->
                    <div id="editor-container"><?php echo $content; ?></div>
                    <input type="hidden" name="content" id="contentInput">
                </div>
            </div>
            
            <!-- Sidebar Column -->
            <div class="right-col" style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.05);">
                
                <div class="form-group">
                    <label class="form-label" style="display: block; margin-bottom: 0.8rem; color: var(--text-secondary); font-size: 0.9rem;">Kapak Görseli</label>
                    <div class="cover-zone" id="coverDropZone">
                        <input type="file" id="coverUpload" accept="image/*" style="display: none;">
                        <input type="hidden" name="cover_image" id="coverImageInput" value="<?php echo htmlspecialchars($coverImage); ?>">
                        <div id="coverPlaceholder" style="<?php echo $coverImage ? 'display:none' : ''; ?>">
                            <i class="ph ph-image" style="font-size: 2.5rem; color: var(--text-secondary); margin-bottom: 0.5rem;"></i>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">Görsel Yükle</p>
                        </div>
                        <img id="coverPreview" src="<?php echo htmlspecialchars($coverImage); ?>" class="cover-preview <?php echo $coverImage ? 'active' : ''; ?>">
                    </div>
                    
                    <!-- AI Search Button -->
                    <button type="button" onclick="openAISearch()" style="width: 100%; margin-top: 1rem; padding: 0.8rem; background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); border: none; border-radius: 0.5rem; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 500; transition: transform 0.2s;">
                        <i class="ph ph-sparkle"></i> Yapay Zeka ile Görsel Bul
                    </button>
                    <small style="display: block; text-align: center; margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.8rem;">Google, Bing ve Yandex üzerinde akıllı arama yapar.</small>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.8rem; color: var(--text-secondary); font-size: 0.9rem;">SEO URL (Slug)</label>
                    <input type="text" id="slugDisplay" class="form-input" 
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); color: var(--text-muted); border-radius: 0.5rem; font-family: monospace; font-size: 0.85rem;" 
                        readonly value="<?php echo htmlspecialchars($slug); ?>">
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.8rem; color: var(--text-secondary); font-size: 0.9rem;">Meta Açıklama</label>
                    <textarea name="description" class="form-input" 
                        style="width: 100%; height: 80px; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 0.5rem; font-size: 0.9rem;"
                        placeholder="Google'da görünecek kısa açıklama..."><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" style="display: block; margin-bottom: 0.8rem; color: var(--text-secondary); font-size: 0.9rem;">Anahtar Kelimeler</label>
                    <input type="text" name="keywords" class="form-input" 
                        style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 0.5rem; font-size: 0.9rem;" 
                        placeholder="Virgülle ayırın..."
                        value="<?php echo htmlspecialchars($keywords); ?>">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <i class="ph ph-paper-plane-right"></i> Yayınla
                </button>
            </div>
        </div>
    </form>
</div>

<!-- AI Search Modal -->
<div id="aiModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(8px);">
    <div class="glass-card" style="width: 800px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; padding: 2rem; background: #1e1e2e; border: 1px solid rgba(255,255,255,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;"><i class="ph ph-robot" style="color: #6c5ce7;"></i> AI Görsel Asistanı</h3>
            <button onclick="document.getElementById('aiModal').style.display='none'" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
            <input type="text" id="aiQueryInput" class="form-input" style="flex: 1; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 0.5rem;" placeholder="Arama terimi...">
            <button onclick="fetchImages(1)" class="btn-primary" style="padding: 0.8rem 1.5rem;">
                <i class="ph ph-magnifying-glass"></i> Ara
            </button>
            <button onclick="loadNextPage()" class="btn-primary" style="padding: 0.8rem 1.5rem; background: #00b894; margin-left: -0.5rem;">
                <i class="ph ph-arrows-clockwise"></i> Değiştir
            </button>
        </div>

        <div id="aiResults" style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; padding-right: 0.5rem;">
            <!-- Images will load here -->
            <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 3rem;">
                Görselleri getirmek için 'Ara' butonuna basın.
            </div>
        </div>

        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
            <small style="color: var(--text-muted);">Görsele tıklayarak kapak fotoğrafı yapabilirsiniz.</small>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="searchEngine('google')" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; color: var(--text-secondary); cursor: pointer; font-size: 0.8rem;">Google'da Aç</button>
                <button onclick="searchEngine('bing')" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; color: var(--text-secondary); cursor: pointer; font-size: 0.8rem;">Bing'de Aç</button>
            </div>
        </div>
    </div>
</div>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
// Initialize Quill
var toolbarOptions = [
    [{ 'header': [2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'align': [] }],
    ['link', 'image', 'video'],
    ['clean']
];

var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: toolbarOptions,
            handlers: {
                image: imageHandler
            }
        }
    },
    placeholder: 'Harika bir içerik oluşturun...'
});

// AI Search Logic
let currentPage = 1;
let englishQuery = ''; // Stored from optimizer
let lastAIImagePrompt = ''; // Stored from Content Generator V2

function openAISearch() {
    const title = document.querySelector('input[name="title"]').value;
    if (!title) {
        alert('Lütfen önce bir yazı başlığı girin.');
        return;
    }

    document.getElementById('aiModal').style.display = 'flex';
    document.getElementById('aiResults').innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><br>Akıllı arama hazırlanıyor...</div>';

    // If we have a fresh prompt from the AI Content Generator, use it!
    if (lastAIImagePrompt) {
        // Clean up the prefix if present
        let cleanedPrompt = lastAIImagePrompt.replace(/^FAL_IMAGE_PROMPT:\s*/i, '');
        document.getElementById('aiQueryInput').value = cleanedPrompt;
        englishQuery = ''; // The prompt is already descriptive
        fetchImages(1);
        // Clear it after use so it doesn't persist forever
        lastAIImagePrompt = '';
        return;
    }

    // Call optimizer API
    fetch('api/optimize_search.php?title=' + encodeURIComponent(title))
        .then(r => r.json())
        .then(data => {
            if (data.turkish_query) {
                document.getElementById('aiQueryInput').value = data.turkish_query;
                englishQuery = data.english_query || '';
                // Auto search
                fetchImages(1);
            } else {
                // Fallback: use cleaned title
                let keywords = title.toLowerCase()
                    .replace(/[0-9]/g, '')
                    .replace(/ ve /g, ' ')
                    .replace(/ ile /g, ' ')
                    .replace(/ için /g, ' ')
                    .trim();
                document.getElementById('aiQueryInput').value = keywords;
                englishQuery = '';
                fetchImages(1);
            }
        })
        .catch(() => {
            // Fallback
            document.getElementById('aiQueryInput').value = title;
            englishQuery = '';
            fetchImages(1);
        });
}

function loadNextPage() {
    currentPage++;
    fetchImages(currentPage);
}

function fetchImages(page = 1) {
    currentPage = page;
    const query = document.getElementById('aiQueryInput').value;
    const resultsContainer = document.getElementById('aiResults');
    
    if (!query) return;

    // Show loading state inline (don't move DOM elements!)
    resultsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);"><i class="ph ph-spinner ph-spin" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>Görseller aranıyor...</div>';

    // Build URL with both Turkish and English queries
    let searchUrl = 'api/search_images.php?q=' + encodeURIComponent(query) + '&page=' + page;
    if (englishQuery) {
        searchUrl += '&q_en=' + encodeURIComponent(englishQuery);
    }

    fetch(searchUrl)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = '';
            
            if (data.results && data.results.length > 0) {
                data.results.forEach(img => {
                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; cursor: pointer; border-radius: 0.5rem; overflow: hidden; aspect-ratio: 16/9; border: 2px solid transparent; transition: all 0.2s;';
                    div.onmouseover = () => { div.style.borderColor = '#6c5ce7'; div.style.transform = 'scale(1.02)'; };
                    div.onmouseout = () => { div.style.borderColor = 'transparent'; div.style.transform = 'scale(1)'; };
                    div.onclick = () => selectImage(img.url);
                    
                    const image = document.createElement('img');
                    image.src = img.thumb;
                    image.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                    // Hide broken images
                    image.onerror = function() {
                        this.closest('div').style.display = 'none';
                    };
                    
                    div.appendChild(image);
                    resultsContainer.appendChild(div);
                });
            } else {
                resultsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: var(--text-muted);">Görsel bulunamadı. Başka bir terim deneyin.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            loading.style.display = 'none';
            resultsContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #ff7675;">Hata oluştu.</div>';
        });
}

function selectImage(url) {
    if (!confirm('Bu görseli indirip kapak fotoğrafı yapmak istiyor musunuz?')) return;

    const resultsContainer = document.getElementById('aiResults');
    resultsContainer.style.opacity = '0.5';
    resultsContainer.style.pointerEvents = 'none';
    
    const formData = new FormData();
    formData.append('url', url);

    fetch('api/upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resultsContainer.style.opacity = '1';
        resultsContainer.style.pointerEvents = 'all';
        
        if (data.location) {
            document.getElementById('coverImageInput').value = data.location;
            // Admin panel is in /admin/, so we need to go up one level to access assets/
            const previewPath = data.location.startsWith('../') ? data.location : '../' + data.location;
            document.getElementById('coverPreview').src = previewPath;
            document.getElementById('coverPreview').classList.add('active');
            document.getElementById('coverPlaceholder').style.display = 'none';
            document.getElementById('aiModal').style.display = 'none';
            // Optional: Insert into Editor too?
            // const range = quill.getSelection(true);
            // quill.insertEmbed(range.index, 'image', data.location);
        } else {
            alert('Görsel indirilemedi: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Bir hata oluştu.');
        resultsContainer.style.opacity = '1';
        resultsContainer.style.pointerEvents = 'all';
    });
}

function searchEngine(engine) {
    const query = document.getElementById('aiQueryInput').value;
    let url = '';
    
    if (engine === 'google') {
        url = 'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(query);
    } else if (engine === 'bing') {
        url = 'https://www.bing.com/images/search?q=' + encodeURIComponent(query);
    } 
    window.open(url, '_blank');
}

function imageHandler() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = () => {
        const file = input.files[0];
        if (/^image\//.test(file.type)) {
            saveToServer(file);
        } else {
            console.warn('You could only upload images.');
        }
    };
}

function saveToServer(file) {
    const fd = new FormData();
    fd.append('file', file);
    
    // Show spinner or placeholder? We'll just wait for now.
    
    fetch('api/upload_image.php', {
        method: 'POST',
        body: fd
    })
    .then(response => response.json())
    .then(result => {
        if (result.location) {
            const range = quill.getSelection();
            // Admin panel is in /admin/ so we need ../ for preview. 
            // BUT for the content saved to DB, we want clean path?
            // Quill inserts <img> tag. If we insert '../assets/...', it will be saved as such.
            // This is tricky. If we save '../assets', it breaks on frontend.
            // accessible via web root.
            // Best approach: Use absolute path if possible, or just accept that admin preview might be broken?
            // OR use a base tag in admin?
            // Actually, Quill content is HTML.
            // If we insert 'assets/...', it breaks in admin editor.
            // If we insert '../assets/...', it works in admin, breaks in frontend.
            
            // Hack: Insert '../assets/...' but before saving, replace '../assets' with 'assets'?
            // Or just insert '../assets/...' and let frontend handle it? 
            // No, frontend is cleaner with 'assets/...'.
            
            // Let's insert the clean path 'assets/...' and hope admin editor styles handle it?
            // No, it won't show.
            
            // Let's insert '../assets/...' for now so user sees it.
            // And in save_blog.php (or just before form submit), we clean it?
            
            // Better: Insert '../assets/...' and let save_blog.php fix it.
            // ERROR: We are inside 'saveToServer' which is called by imageHandler.
            
            // Let's stick to consistent relative paths.
            // If we return 'assets/...', we prepend '../' for display.
            
            const displayUrl = result.location.startsWith('../') ? result.location : '../' + result.location;
            quill.insertEmbed(range.index, 'image', displayUrl);
        } else {
            alert('Görsel yüklenemedi: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Görsel yüklenemedi.');
    });
}

// Slug Generator
function generateSlug(title) {
    // Only generate if creating new or if slug is empty
    const slugInput = document.getElementById('slugInputValue');
    const slugDisplay = document.getElementById('slugDisplay');
    const existingSlug = '<?php echo $slug; ?>';
    
    // If updating existing post, don't auto-change slug unless user cleared it?
    // Actually, safest is to only do it if creating new.
    if (existingSlug && existingSlug.trim() !== '') return;

    let slug = title.toLowerCase()
        .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
        .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-') + '.html';
        
    slugInput.value = slug;
    slugDisplay.value = slug;
}

// Cover Image Logic
const dropZone = document.getElementById('coverDropZone');
const fileInput = document.getElementById('coverUpload');
const preview = document.getElementById('coverPreview');
const hiddenInput = document.getElementById('coverImageInput');
const placeholder = document.getElementById('coverPlaceholder');

dropZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', handleFileSelect);

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#6c5ce7';
    dropZone.style.background = 'rgba(108, 92, 231, 0.1)';
});
dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = 'rgba(255,255,255,0.1)';
    dropZone.style.background = 'rgba(255,255,255,0.02)';
});
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect();
    }
});

function handleFileSelect() {
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    fetch('api/upload_image.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.location) {
            hiddenInput.value = data.location;
            preview.src = data.location;
            preview.classList.add('active');
            placeholder.style.display = 'none';
        } else {
            alert('Hata: ' + data.error);
        }
    });
}

// Form Submit
document.getElementById('blogForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Populate hidden content input with Quill HTML
    // If AI content was inserted, use the raw HTML instead of Quill's stripped version
    if (window._aiRawContent) {
        document.getElementById('contentInput').value = window._aiRawContent;
    } else {
        document.getElementById('contentInput').value = quill.root.innerHTML;
    }

    const formData = new FormData(this);

    fetch('api/save_blog.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Yazı başarıyla yayınlandı!');
            window.location.href = 'blogs.php';
        } else {
            alert('❌ Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu.');
    });
});
</script>

<!-- ═══════════════════════════════════════════════ -->
<!-- AI İÇERİK ASISTANI MODAL                       -->
<!-- ═══════════════════════════════════════════════ -->
<div id="aiContentModal2" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; justify-content: center; align-items: center; backdrop-filter: blur(8px);">
    <div style="background: linear-gradient(145deg, #1a1a2e, #16213e); border-radius: 1.2rem; width: 900px; max-width: 95vw; max-height: 92vh; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 25px 60px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden;">

        <!-- Header -->
        <div style="padding: 1.2rem 1.8rem; background: linear-gradient(135deg, rgba(108,92,231,0.12), rgba(0,229,255,0.08)); border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.7rem;">
                <i class="ph ph-robot" style="font-size: 1.3rem; color: #a29bfe;"></i>
                <h3 style="margin: 0; font-size: 1.1rem; color: #f0f0ff;">AI İçerik Asistanı</h3>
                <div id="statusPill" style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 50px; background: rgba(0,184,148,0.12); border: 1px solid rgba(0,184,148,0.2); font-size: 0.72rem;">
                    <span id="statusDot" style="width: 6px; height: 6px; border-radius: 50%; background: #00b894; display: inline-block;"></span>
                    <span id="statusText" style="color: #00b894;">Hazır</span>
                </div>
            </div>
            <button onclick="closeAIModal2()" style="background: rgba(255,255,255,0.08); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">&times;</button>
        </div>

        <!-- Body: Two columns -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; flex: 1; overflow: hidden; min-height: 0;">

            <!-- LEFT: Config + Log -->
            <div style="padding: 1.4rem; border-right: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 1rem; overflow-y: auto;">

                <!-- Topic input -->
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: rgba(255,255,255,0.5); font-size: 0.82rem;">🎯 Konu</label>
                    <input type="text" id="aiTopicInput" placeholder="Örn: İstanbul'da öğrenci yaşam maliyeti 2026" style="width: 100%; padding: 0.8rem 1rem; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 0.6rem; font-size: 0.95rem; outline: none;" onfocus="this.style.borderColor='#6c5ce7'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" onkeypress="if(event.key==='Enter') runGenerate()">
                </div>

                <!-- Tone -->
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: rgba(255,255,255,0.5); font-size: 0.82rem;">🎭 Ton</label>
                    <div id="toneGroup" style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <span class="ai-chip active" data-val="coach" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(0,229,255,0.35); background: rgba(0,229,255,0.12); color: #00e5ff; cursor: pointer;"><i class="ph ph-graduation-cap"></i> Akademik</span>
                        <span class="ai-chip" data-val="analyst" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.55); cursor: pointer;"><i class="ph ph-chat-circle-dots"></i> Samimi</span>
                        <span class="ai-chip" data-val="neutral" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.55); cursor: pointer;"><i class="ph ph-briefcase"></i> Profesyonel</span>
                    </div>
                </div>

                <!-- Depth -->
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: rgba(255,255,255,0.5); font-size: 0.82rem;">📏 Derinlik</label>
                    <div id="depthGroup" style="display: flex; gap: 0.4rem;">
                        <span class="ai-chip" data-val="short" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.55); cursor: pointer;">Kısa</span>
                        <span class="ai-chip" data-val="balanced" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.55); cursor: pointer;">Orta</span>
                        <span class="ai-chip active" data-val="deep" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(0,229,255,0.35); background: rgba(0,229,255,0.12); color: #00e5ff; cursor: pointer;">4K+ Detaylı</span>
                    </div>
                </div>

                <!-- Language -->
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: rgba(255,255,255,0.5); font-size: 0.82rem;">🌐 Dil</label>
                    <div id="langGroup" style="display: flex; gap: 0.4rem;">
                        <span class="ai-chip active" data-val="tr" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(0,229,255,0.35); background: rgba(0,229,255,0.12); color: #00e5ff; cursor: pointer;">TR</span>
                        <span class="ai-chip" data-val="en" style="padding: 5px 12px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.55); cursor: pointer;">EN</span>
                    </div>
                </div>

                <!-- Generate button -->
                <button type="button" id="generateBtn" onclick="runGenerate()" style="width: 100%; padding: 0.85rem; background: linear-gradient(135deg, #6c5ce7, #0984e3); border: none; border-radius: 0.7rem; color: white; cursor: pointer; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.3s; box-shadow: 0 4px 15px rgba(108,92,231,0.35);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="ph ph-rocket-launch" style="font-size: 1.1rem;"></i> Üret
                </button>

                <!-- Agent Log -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label style="color: rgba(255,255,255,0.5); font-size: 0.82rem;">⬛ Ajan Aktivite Logu</label>
                        <span id="sourceBadge" style="display: none; padding: 2px 8px; border-radius: 50px; background: rgba(108,92,231,0.15); border: 1px solid rgba(108,92,231,0.3); color: #a29bfe; font-size: 0.72rem; font-weight: 600;"><span id="sourceCount">0</span> kaynak</span>
                    </div>
                    <div id="logPane" style="max-height: 150px; overflow-y: auto; padding: 0.6rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); border-radius: 0.5rem; font-family: 'Consolas', 'Monaco', monospace; font-size: 0.75rem;">
                        <div style="padding: 3px 0; color: rgba(255,255,255,0.4);"><span style="color: #636e72; margin-right: 6px;">00:00</span> Sistem hazır. Konu girin ve "Üret" butonuna basın.</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Live Preview -->
            <div style="display: flex; flex-direction: column; overflow: hidden;">
                <div style="padding: 0.8rem 1.4rem; border-bottom: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.5); font-size: 0.82rem;">📄 Canlı Önizleme</div>
                <div id="renderScroll" style="flex: 1; overflow-y: auto; padding: 1.2rem 1.4rem;">
                    <!-- Idle state -->
                    <div id="idleState" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 12px; opacity: 0.3;">
                        <i class="ph ph-robot" style="font-size: 3rem;"></i>
                        <span style="font-size: 0.88rem; color: rgba(255,255,255,0.5);">Üretim başladıktan sonra içerik burada görünecek</span>
                    </div>
                    <!-- Skeleton (loading) -->
                    <div id="skeletonState" style="display: none;">
                        <div style="height: 22px; width: 70%; background: rgba(255,255,255,0.06); border-radius: 6px; animation: skPulse 1.2s ease-in-out infinite;"></div>
                        <div style="height: 14px; width: 50%; background: rgba(255,255,255,0.04); border-radius: 4px; margin-top: 10px; animation: skPulse 1.2s ease-in-out infinite 0.2s;"></div>
                        <div style="height: 12px; width: 100%; background: rgba(255,255,255,0.03); border-radius: 4px; margin-top: 18px; animation: skPulse 1.2s ease-in-out infinite 0.3s;"></div>
                        <div style="height: 12px; width: 95%; background: rgba(255,255,255,0.03); border-radius: 4px; margin-top: 6px; animation: skPulse 1.2s ease-in-out infinite 0.4s;"></div>
                        <div style="height: 12px; width: 80%; background: rgba(255,255,255,0.03); border-radius: 4px; margin-top: 6px; animation: skPulse 1.2s ease-in-out infinite 0.5s;"></div>
                        <div style="height: 80px; width: 100%; background: rgba(255,255,255,0.04); border-radius: 8px; margin-top: 18px; animation: skPulse 1.2s ease-in-out infinite 0.6s;"></div>
                        <div style="height: 12px; width: 100%; background: rgba(255,255,255,0.03); border-radius: 4px; margin-top: 18px; animation: skPulse 1.2s ease-in-out infinite 0.7s;"></div>
                        <div style="height: 12px; width: 90%; background: rgba(255,255,255,0.03); border-radius: 4px; margin-top: 6px; animation: skPulse 1.2s ease-in-out infinite 0.8s;"></div>
                    </div>
                    <!-- Generated content -->
                    <div id="contentOutput" style="display: none;"></div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div id="modalFooter" style="display: none; padding: 1rem 1.8rem; border-top: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2); justify-content: flex-end; gap: 0.6rem;">
            <button type="button" onclick="copyContent()" style="padding: 0.6rem 1.2rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.5rem; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 0.4rem;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <i class="ph ph-copy"></i> Kopyala
            </button>
            <button type="button" onclick="insertToEditor()" style="padding: 0.6rem 1.4rem; background: linear-gradient(135deg, #00b894, #00cec9); border: none; border-radius: 0.5rem; color: white; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 12px rgba(0,184,148,0.3);" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="ph ph-check-circle"></i> Onayla & İçeriğe Aktar
            </button>
        </div>

    </div>
</div>

<style>
/* Skeleton pulse animation */
@keyframes skPulse {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
}
/* Preview content styles */
#renderScroll h2 { font-size:1.25rem; color:#f0f0ff; margin-top:1.5rem; margin-bottom:.6rem; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:.4rem; }
#renderScroll h3 { font-size:1rem; color:#00e5ff; margin-top:1.2rem; margin-bottom:.4rem; }
#renderScroll h4 { font-size:.85rem; color:#fab1a0; margin-top:.8rem; text-transform:uppercase; letter-spacing:1px; }
#renderScroll p  { font-size:.85rem; line-height:1.75; color:#b2bec3; margin-bottom:.8rem; }
#renderScroll ul, #renderScroll ol { padding-left:1.3rem; margin-bottom:.8rem; color:#b2bec3; }
#renderScroll li { margin-bottom:.3rem; font-size:.82rem; }
#renderScroll strong { color:#f0f0ff; }
#renderScroll .blog-card-glass { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:1rem; margin:1rem 0; }
#renderScroll .blog-table-premium { width:100%; border-collapse:separate; border-spacing:0; border-radius:8px; overflow:hidden; background:rgba(30,30,46,0.6); margin:.8rem 0; }
#renderScroll .blog-table-premium th { background:linear-gradient(135deg,rgba(108,92,231,0.2),rgba(9,132,227,0.2)); padding:.6rem .7rem; color:#a29bfe; text-align:left; font-weight:700; border-bottom:1px solid rgba(255,255,255,0.05); font-size:.78rem; }
#renderScroll .blog-table-premium td { padding:.5rem .7rem; border-bottom:1px solid rgba(255,255,255,0.02); color:#b2bec3; font-size:.78rem; }
#renderScroll .blog-alert-gradient { padding:.7rem .9rem; border-radius:8px; margin:.8rem 0; color:white; font-size:.82rem; }
#renderScroll .blog-alert-gradient.info { background:linear-gradient(135deg,#0984e3,#6c5ce7); }
#renderScroll .blog-alert-gradient.tip  { background:linear-gradient(135deg,#00b894,#00cec9); }
#renderScroll .blog-alert-gradient.warning { background:linear-gradient(135deg,#fdcb6e,#e17055); color:#2d3436; }
#renderScroll .blog-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(100px,1fr)); gap:.6rem; margin:.8rem 0; }
#renderScroll .blog-stat-item { text-align:center; padding:.6rem; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.05); }
#renderScroll .blog-stat-value { display:block; font-size:1.2rem; font-weight:800; color:#a29bfe; }
#renderScroll .blog-stat-label { font-size:.65rem; color:#636e72; text-transform:uppercase; }
#renderScroll .blog-concept-chip { display:inline-block; padding:2px 8px; background:rgba(108,92,231,0.15); border:1px solid rgba(108,92,231,0.3); border-radius:50px; color:#a29bfe; font-size:.72rem; font-weight:600; margin:2px; }
#renderScroll .blog-schema-hidden { display:none; }
</style>

<?php require_once 'footer.php'; ?>

<script>
// ═══════════════════════════════════════════
// AI İÇERİK ASISTANI — MODAL ENGINE V16
// ═══════════════════════════════════════════

let generatedData = null;
let aiStartTime = Date.now();
const aiState = { tone: 'coach', depth: 'deep', lang: 'tr' };

// ── Modal open/close ──
function openAIModal2() {
    const m = document.getElementById('aiContentModal2');
    m.style.display = 'flex';
    // Pre-fill topic from title
    const title = document.querySelector('input[name="title"]')?.value;
    const topicInput = document.getElementById('aiTopicInput');
    if (title && !topicInput.value) topicInput.value = title;
    setTimeout(() => topicInput.focus(), 200);
}

function closeAIModal2() {
    document.getElementById('aiContentModal2').style.display = 'none';
}

// Close on backdrop
document.getElementById('aiContentModal2')?.addEventListener('click', function(e) {
    if (e.target === this) closeAIModal2();
});

// ── Chip toggle ──
function setupChipGroup(groupId, stateKey) {
    const group = document.getElementById(groupId);
    if (!group) return;
    group.addEventListener('click', function(e) {
        const chip = e.target.closest('.ai-chip');
        if (!chip) return;
        // Reset all chips in this group
        group.querySelectorAll('.ai-chip').forEach(c => {
            c.style.background = 'rgba(255,255,255,0.04)';
            c.style.borderColor = 'rgba(255,255,255,0.1)';
            c.style.color = 'rgba(255,255,255,0.55)';
            c.classList.remove('active');
        });
        // Activate clicked
        chip.style.background = 'rgba(0,229,255,0.12)';
        chip.style.borderColor = 'rgba(0,229,255,0.35)';
        chip.style.color = '#00e5ff';
        chip.classList.add('active');
        aiState[stateKey] = chip.dataset.val;
    });
}
setupChipGroup('toneGroup', 'tone');
setupChipGroup('depthGroup', 'depth');
setupChipGroup('langGroup', 'lang');

// ── Status pill ──
function setStatus(state) {
    const dot  = document.getElementById('statusDot');
    const text = document.getElementById('statusText');
    const pill = document.getElementById('statusPill');
    const map = {
        ready:     { color: '#00b894', label: 'Hazır' },
        searching: { color: '#74b9ff', label: 'Araştırıyor…' },
        writing:   { color: '#fdcb6e', label: 'Yazıyor…' },
        rendering: { color: '#a29bfe', label: 'Render…' },
        done:      { color: '#00b894', label: 'Tamamlandı ✓' },
        error:     { color: '#ff7675', label: 'Hata' },
    };
    const s = map[state] || map.ready;
    dot.style.background = s.color;
    dot.style.boxShadow = `0 0 6px ${s.color}`;
    text.textContent = s.label;
    text.style.color = s.color;
    pill.style.borderColor = s.color + '33';
    pill.style.background = s.color + '1a';
}

// ── Log helper ──
function aiLog(msg, type = 'info') {
    const el = document.getElementById('logPane');
    const elapsed = ((Date.now() - aiStartTime) / 1000).toFixed(0);
    const ts = String(Math.floor(elapsed / 60)).padStart(2, '0') + ':' + String(elapsed % 60).padStart(2, '0');
    const colors = { info: 'rgba(255,255,255,0.4)', warn: '#fdcb6e', err: '#ff7675', ok: '#55efc4' };
    const div = document.createElement('div');
    div.style.cssText = `padding: 3px 0; color: ${colors[type] || colors.info}; border-bottom: 1px solid rgba(255,255,255,0.02);`;
    div.innerHTML = `<span style="color: #636e72; margin-right: 6px;">${ts}</span> ${msg}`;
    el.appendChild(div);
    el.scrollTop = el.scrollHeight;
}

// ── Show skeleton ──
function showSkeleton() {
    document.getElementById('idleState').style.display      = 'none';
    document.getElementById('skeletonState').style.display   = 'block';
    document.getElementById('contentOutput').style.display    = 'none';
}

// ── Show content ──
function showContent(html) {
    document.getElementById('skeletonState').style.display = 'none';
    const out = document.getElementById('contentOutput');
    out.style.display = 'block';
    out.innerHTML = html;
}

// ── Main generate ──
async function runGenerate() {
    const topic = document.getElementById('aiTopicInput').value.trim();
    if (!topic) {
        document.getElementById('aiTopicInput').style.borderColor = '#ff7675';
        setTimeout(() => document.getElementById('aiTopicInput').style.borderColor = 'rgba(255,255,255,0.1)', 1500);
        return;
    }

    // Reset
    document.getElementById('modalFooter').style.display = 'none';
    document.getElementById('sourceBadge').style.display = 'none';
    generatedData = null;
    aiStartTime = Date.now();

    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.innerHTML = '<i class="ph ph-circle-notch" style="animation:aiSpin 1s linear infinite;"></i> Araştırılıyor…';

    // Inject spin animation
    if (!document.getElementById('aiSpinStyle')) {
        const s = document.createElement('style');
        s.id = 'aiSpinStyle';
        s.textContent = '@keyframes aiSpin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
        document.head.appendChild(s);
    }

    showSkeleton();
    setStatus('searching');
    aiLog(`🚀 <strong>"${topic}"</strong> için araştırma başlatıldı`, 'info');

    // Progress messages
    const msgs = [
        [800,  'info', '🔍 DuckDuckGo araması yapılıyor…'],
        [2500, 'info', '🌐 Web sayfaları indiriliyor…'],
        [5000, 'warn', '⚙️ İçerikler temizleniyor…'],
        [8000, 'info', '🧠 Gemini Flash AI modeline gönderiliyor…'],
        [12000,'info', '✍️  İçerik sentezleniyor…'],
        [18000,'info', '📊 Çıktı ayrıştırılıyor…'],
    ];
    const timers = msgs.map(([delay, type, msg]) => setTimeout(() => aiLog(msg, type), delay));

    try {
        setStatus('writing');
        const url = `api/ai_generate.php?topic=${encodeURIComponent(topic)}&tone=${aiState.tone}&depth=${aiState.depth}&lang=${aiState.lang}`;
        const res = await fetch(url);
        const data = await res.json();

        timers.forEach(t => clearTimeout(t));

        if (data.error) {
            setStatus('error');
            aiLog(`❌ ${data.error}`, 'err');
            showContent(`<div style="color:#ff7675;padding:2rem;text-align:center;"><i class="ph ph-warning-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>${data.error}</div>`);
        } else {
            generatedData = data;
            setStatus('rendering');
            aiLog(`✅ İçerik alındı (${data.source_count || '?'} kaynak)`, 'ok');

            if (data.source_count) {
                document.getElementById('sourceCount').textContent = data.source_count;
                document.getElementById('sourceBadge').style.display = 'inline';
            }

            showContent(data.content || '<p>İçerik alınamadı.</p>');
            aiLog('🎨 Render tamamlandı', 'ok');

            // Show footer
            document.getElementById('modalFooter').style.display = 'flex';
            setStatus('done');
            aiLog('🎉 Hazır! İçeriği inceleyip "Onayla & İçeriğe Aktar" ile editöre gönderin.', 'ok');
        }
    } catch (err) {
        timers.forEach(t => clearTimeout(t));
        setStatus('error');
        aiLog(`❌ Bağlantı hatası: ${err.message}`, 'err');
        showContent(`<div style="color:#ff7675;padding:2rem;text-align:center;">Sunucu bağlantı hatası.</div>`);
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = '<i class="ph ph-rocket-launch" style="font-size: 1.1rem;"></i> Üret';
    }
}

// ── Insert to editor ──
function insertToEditor() {
    if (!generatedData) return;

    if (generatedData.content) {
        // Store raw HTML for form submission
        window._aiRawContent = generatedData.content;
        document.getElementById('contentInput').value = generatedData.content;

        // Hide Quill toolbar and editor
        const toolbar = document.querySelector('.ql-toolbar');
        const editorContainer = document.getElementById('editor-container');
        if (toolbar) toolbar.style.display = 'none';
        if (editorContainer) editorContainer.style.display = 'none';

        // Create or update the static preview overlay
        let preview = document.getElementById('ai-content-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'ai-content-preview';
            preview.style.cssText = 'background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08); border-radius: 0.5rem; padding: 1.5rem; min-height: 300px; max-height: 500px; overflow-y: auto; color: #b2bec3; font-size: 0.9rem; line-height: 1.7; position: relative;';
            editorContainer.parentNode.insertBefore(preview, editorContainer);
        }
        preview.innerHTML = generatedData.content;
        preview.style.display = 'block';

        // Add a small edit button to switch back to Quill
        let editBtn = document.getElementById('ai-switch-to-quill');
        if (!editBtn) {
            editBtn = document.createElement('button');
            editBtn.id = 'ai-switch-to-quill';
            editBtn.type = 'button';
            editBtn.style.cssText = 'margin-top: 0.5rem; padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.4rem; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 0.78rem; display: flex; align-items: center; gap: 0.3rem;';
            editBtn.innerHTML = '<i class="ph ph-pencil-simple"></i> Manuel Düzenle (Quill)';
            editBtn.onclick = function() {
                // Switch back to Quill with plain text version
                preview.style.display = 'none';
                editBtn.style.display = 'none';
                if (toolbar) toolbar.style.display = '';
                if (editorContainer) editorContainer.style.display = '';
                window._aiRawContent = null; // Clear flag so form uses Quill content
            };
            preview.parentNode.insertBefore(editBtn, preview.nextSibling);
        }
        editBtn.style.display = 'flex';
    }
    if (generatedData.title) {
        const ti = document.querySelector('input[name="title"]');
        if (ti) { ti.value = generatedData.title; generateSlug(generatedData.title); }
    }
    if (generatedData.description) {
        const di = document.querySelector('textarea[name="description"]');
        if (di) di.value = generatedData.description;
    }
    if (generatedData.keywords) {
        const ki = document.querySelector('input[name="keywords"]');
        if (ki) ki.value = generatedData.keywords;
    }

    closeAIModal2();

    // Toast
    const notif = document.createElement('div');
    notif.style.cssText = 'position:fixed;top:80px;right:24px;background:linear-gradient(135deg,#00b894,#00cec9);color:white;padding:14px 22px;border-radius:12px;font-weight:600;font-size:0.9rem;z-index:99999;box-shadow:0 8px 24px rgba(0,184,148,0.3);transform:translateX(120%);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);';
    notif.innerHTML = '<i class="ph ph-check-circle" style="margin-right:6px;"></i> AI içeriği editöre aktarıldı!';
    document.body.appendChild(notif);
    requestAnimationFrame(() => notif.style.transform = 'translateX(0)');
    setTimeout(() => { notif.style.transform = 'translateX(120%)'; setTimeout(() => notif.remove(), 400); }, 3500);
}

// ── Copy ──
function copyContent() {
    if (!generatedData) return;
    navigator.clipboard.writeText(generatedData.content || '').then(() => aiLog('📋 Panoya kopyalandı', 'ok'));
}
</script>
