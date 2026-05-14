// =============================================
// DATA.JS — Üniversite Bütçe Hesaplayıcı Statik Veritabanı
// İller veritabanından dinamik olarak çekilmektedir (api/dynamic_data.js.php)
// Diğer ayarlar sabit olarak burada bulunur.
// =============================================

const KYK_CREDIT_OPTIONS = [
    { label: "KYK Kredi (Ön Lisans)", amount: 4000, repaymentYears: 4 },
    { label: "KYK Kredi (Lisans)", amount: 4000, repaymentYears: 4 },
    { label: "KYK Kredi (Yüksek Lisans)", amount: 8000, repaymentYears: 4 },
    { label: "KYK Kredi (Doktora)", amount: 12000, repaymentYears: 4 },
    { label: "KYK Burs", amount: 4000, repaymentYears: 0 },
    { label: "Almıyorum", amount: 0, repaymentYears: 0 }
];

const EXPENSE_CATEGORIES = [
    { id: "housing", name: "Barınma", icon: "🏠", color: "#6C5CE7", min: 0, max: 50000, step: 500, default: 10000, subcategories: ["KYK Yurdu", "Özel Yurt", "Kiralık Ev", "Paylaşımlı Ev", "Aile Evi"] },
    { id: "food", name: "Yemek", icon: "🍽️", color: "#00B894", min: 0, max: 50000, step: 250, default: 6000, subcategories: ["Yemekhane", "Dışarıda Yemek", "Market Alışverişi", "Ev Yemekleri", "Çay-Kahve"] },
    { id: "transport", name: "Ulaşım", icon: "🚌", color: "#0984E3", min: 0, max: 50000, step: 50, default: 900, subcategories: ["Öğrenci Akbil", "Dolmuş", "Araç", "Bisiklet", "Yürüyüş"] },
    { id: "education", name: "Eğitim", icon: "📚", color: "#FDCB6E", min: 0, max: 50000, step: 50, default: 800, subcategories: ["Kırtasiye", "Kitap", "Fotokopi", "Online Abonelik", "Kurs"] },
    { id: "personal", name: "Kişisel", icon: "👤", color: "#E17055", min: 0, max: 50000, step: 100, default: 1500, subcategories: ["Giyim", "Kozmetik", "Sağlık", "Telefon & İnternet", "Kuaför"] },
    { id: "social", name: "Sosyal", icon: "🎉", color: "#A29BFE", min: 0, max: 50000, step: 50, default: 1000, subcategories: ["Sinema", "Cafe", "Hobi", "Spor Salonu", "Konser & Etkinlik"] },
    { id: "utilities", name: "Faturalar", icon: "🔌", color: "#00CEC9", min: 0, max: 50000, step: 100, default: 2500, subcategories: ["Elektrik Faturası", "Su Faturası", "Doğalgaz Faturası", "İnternet Faturası", "Aidat"] },
    { id: "emergency", name: "Acil Durum", icon: "🚨", color: "#FF6B6B", min: 0, max: 50000, step: 50, default: 500, subcategories: ["Beklenmedik Gider Fonu", "Sağlık Acil", "Tamir & Bakım"] }
];

const AI_TIPS = {
    housing: [
        { threshold: 15000, tip: "🏠 Ev arkadaşı bularak kiranızı yarıya düşürebilirsiniz! Paylaşımlı evler %40-60 tasarruf sağlar." },
        { threshold: 8000, tip: "🏠 KYK yurduna başvuru yapmayı düşünün, aylık sadece 1.000₺ ile barınma sorununuzu çözebilirsiniz." },
        { threshold: 5000, tip: "🏠 Barınma gideriniz makul seviyede. Kampüse yakın konumları tercih ederek ulaşım masrafını da azaltabilirsiniz." }
    ],
    food: [
        { threshold: 8000, tip: "🍽️ Haftada 3-4 gün yemekhanede yemek yiyerek aylık 3.000₺ tasarruf edebilirsiniz." },
        { threshold: 6000, tip: "🍽️ Haftalık meal-prep ile hem sağlıklı hem ekonomik beslenebilirsiniz. Market alışverişini liste ile yapın." },
        { threshold: 4000, tip: "🍽️ Harika bir bütçe yönetimi! Öğrenci yemekhanelerinden faydalanmaya devam edin." }
    ],
    transport: [
        { threshold: 1500, tip: "🚌 Öğrenci akbil/abonman kartı ile aylık ulaşım masrafınızı sabitleyebilirsiniz." },
        { threshold: 900, tip: "🚌 Bisiklet kullanarak hem tasarruf edin hem sağlıklı kalın." },
        { threshold: 500, tip: "🚌 Ulaşım gideriniz çok iyi! Kampüse yürüme mesafesi büyük avantaj." }
    ],
    education: [
        { threshold: 2000, tip: "📚 Kütüphaneden kitap ödünç alarak %60 tasarruf sağlayabilirsiniz." },
        { threshold: 1000, tip: "📚 İkinci el kitap gruplarına katılın, üniversitenizin kitap takas grupları var." },
        { threshold: 500, tip: "📚 GitHub Student Pack ile ücretsiz yazılım araçlarına erişebilirsiniz." }
    ],
    personal: [
        { threshold: 4000, tip: "👤 Üniversite sağlık merkezini kullanın, telefon paketinizi öğrenci tarifesine geçirin." },
        { threshold: 2500, tip: "👤 Sezon sonu indirimlerini takip edin, online outlet'lerden %40 tasarruf edin." },
        { threshold: 1200, tip: "👤 Kişisel giderleriniz iyi seviyede. Üniversite eczanesini tercih edin!" }
    ],
    social: [
        { threshold: 3000, tip: "🎉 Üniversite kulüplerine katılarak ücretsiz aktivitelere erişebilirsiniz." },
        { threshold: 1500, tip: "🎉 Öğrenci indirimi sunan mekânları tercih edin, Çarşamba sinema %50!" },
        { threshold: 700, tip: "🎉 Harika denge! Doğa yürüyüşleri ve kampüs etkinlikleri ücretsiz." }
    ],
    emergency: [
        { threshold: 0, tip: "🚨 Gelirinizin en az %10'unu acil durum fonu olarak ayırın." }
    ],
    utilities: [
        { threshold: 4000, tip: "🔌 Enerji tasarruflu ampuller ve akıllı priz kullanarak elektrik faturanızı %20 düşürebilirsiniz." },
        { threshold: 2500, tip: "🔌 Ev arkadaşlarınızla faturaları bölüşerek kişi başı maliyeti ciddi ölçüde azaltabilirsiniz." },
        { threshold: 1500, tip: "🔌 Fatura giderleriniz iyi seviyede! KYK yurdunda kalıyorsanız faturalar zaten dahildir." }
    ]
};

const GENERAL_TIPS = [
    "💡 Öğrenci kimliğinizi her yerde gösterin! Müze, sinema, tiyatro ve birçok restoranda indirim alabilirsiniz.",
    "💡 Spotify, Apple Music ve YouTube Premium öğrenci planları %50 indirimli.",
    "💡 Üniversitenizin kütüphanesi birçok online veritabanına ücretsiz erişim sağlar.",
    "💡 Microsoft Office 365 ve Adobe Creative Cloud öğrencilere ücretsiz/indirimli.",
    "💡 Harcamalarınızı bir uygulamayla takip edin. Farkındalık bile %15 tasarruf sağlar.",
    "💡 Mevsim meyve-sebzelerini tercih edin, hem sağlıklı hem ekonomik.",
    "💡 Kıyafet takası etkinlikleri düzenleyin, arkadaşlarınızla gardırop paylaşın.",
    "💡 Ücretsiz online kurslar (Coursera, edX) ile kendinizi geliştirin, para harcamadan!"
];

const FAQ_DATA = [
    { question: "KYK kredisi nasıl alınır ve geri ödeme koşulları nelerdir?", answer: "KYK kredisine <strong>kfrm.kyk.gov.tr</strong> üzerinden başvurabilirsiniz. Başvurular genellikle Eylül-Ekim aylarında açılır. 2026 itibarıyla ön lisans ve lisans öğrencilerine aylık 4.000₺, yüksek lisansa 8.000₺, doktoraya 12.000₺ kredi verilmektedir. Kredi, mezuniyetten 2 yıl sonra geri ödenmeye başlar." },
    { question: "Hangi burslar öğrencilere açıktır?", answer: "<strong>KYK Bursu</strong> (aylık 4.000₺, geri ödemesiz), <strong>TÜBİTAK Bursları</strong> (lisans 3.500₺, Y.Lisans 5.000₺), <strong>Vakıf Bursları</strong> (TEV, Sabancı, Koç — 2.000-4.000₺), <strong>Belediye Bursları</strong> ve <strong>Özel Sektör Bursları</strong>. Her birinin koşulları farklıdır." },
    { question: "KYK yurduna nasıl başvurulur?", answer: "KYK yurt başvuruları <strong>yurtkur.kyk.gov.tr</strong> üzerinden e-Devlet ile yapılır. Gelir durumu, aile bilgileri ve tercih sıralaması puanlamayı etkiler. 2025-2026 dönemi yurt ücretleri aylık 725₺ ile 1.200₺ arasında değişmektedir." },
    { question: "Öğrenci olarak vergi indirimi var mı?", answer: "Part-time çalışmalarda asgari ücretin altındaki gelirler vergiden muaftır. Belediyeler <strong>su, doğalgaz ve toplu taşıma</strong> indirimli tarifeleri sunar." },
    { question: "Paylaşımlı ev tutarken nelere dikkat etmeliyim?", answer: "Kontrat <strong>yazılı</strong> olsun, depozito makbuzu alın, fatura paylaşımını önceden belirleyin. <strong>DASK sigortası</strong> olduğundan emin olun. Şubat 2026 kira artış sınırı %33,98'dir." },
    { question: "Öğrenci indirimi nerelerden alınır?", answer: "<strong>Toplu taşıma</strong>, <strong>müze ve ören yerleri</strong>, <strong>sinema</strong>, <strong>tiyatro</strong>, birçok <strong>restoran ve kafe</strong>, <strong>yazılım ve teknoloji</strong> ürünlerinde indirim alabilirsiniz." },
    { question: "Part-time iş bulmanın en iyi yolları nelerdir?", answer: "Üniversite <strong>Kariyer Merkezi</strong>, <strong>kampüs içi ilanlar</strong>, <strong>freelance platformları</strong> (Upwork, Fiverr), <strong>özel ders</strong> ve <strong>staj programları</strong> iyi seçeneklerdir." },
    { question: "Aylık bütçemi nasıl planlamalıyım?", answer: "Sabit giderleri belirleyin. Kalanın <strong>%50</strong>'sini zorunlu ihtiyaçlara, <strong>%30</strong>'unu sosyal harcamalara, <strong>%10</strong>'unu acil duruma, <strong>%10</strong>'unu tasarrufa ayırın." }
];

const MOTIVATIONAL_QUOTES = [
    "💪 \"Küçük tasarruflar büyük birikimlere dönüşür.\"",
    "🎯 \"Bütçe yapmak özgürlüğe giden yoldur.\"",
    "🌟 \"Bugünün planı, yarının güvencesidir.\"",
    "📈 \"Her kuruş bilinçli harcandığında değer kazanır.\"",
    "🏆 \"Disiplin geçici, finansal özgürlük kalıcıdır.\"",
    "💰 \"Para yönetimi, hayat yönetiminin temelidir.\"",
    "🚀 \"Üniversitede kazandığın finansal alışkanlıklar ömür boyu seninle kalır.\""
];
