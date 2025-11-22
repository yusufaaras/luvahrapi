<?php
// forms/cv-send.php
// CV gönderimlerini veritabanına kaydeder ve CV dosyalarını uploads/cvs/ dizinine koyar.

// --- Ayarlar ---
// Öncelikle bir ortam değişkeni DATABASE_URL kullanmayı deniyoruz.
// Eğer sunucunuzda ortam değişkeni yoksa, aşağıdaki $fallbackDatabaseUrl değişkenine
// verdiğiniz bağlantı stringini koyabilirsiniz (kullanıcının verdiği örnek):
// mysql://root:ysfars123321@127.0.0.1:3306/luvahrdb
$databaseUrl = getenv('DATABASE_URL') ?: 'mysql://root:ysfars123321@127.0.0.1:3306/luvahrdb';
// Parse DATABASE_URL
function parseDatabaseUrl($url) {
    $parts = parse_url($url);
    if ($parts === false) return null;
    return [
        'host' => $parts['host'] ?? '127.0.0.1',
        'port' => $parts['port'] ?? 3306,
        'user' => $parts['user'] ?? '',
        'pass' => $parts['pass'] ?? '',
        'dbname' => isset($parts['path']) ? ltrim($parts['path'], '/') : ''
    ];
}

$db = parseDatabaseUrl($databaseUrl);
if (!$db || !$db['dbname']) {
    http_response_code(500);
    echo "Veritabanı bağlantı bilgileri okunamadı. Lütfen DATABASE_URL ayarını kontrol edin.";
    exit;
}

$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
} catch (Exception $e) {
    http_response_code(500);
    // Hata detayını production'da göstermeyin; loglayın.
    error_log("DB connection error: " . $e->getMessage());
    echo "Veritabanına bağlanılamadı.";
    exit;
}

// Gerekli tabloları yoksa oluştur (basit migration)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            expertise VARCHAR(255) NULL,
            stored_filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // (Opsiyonel) İletişim formu ayrı tutulmak istenirse messages tablosu:
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NULL,
            message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    error_log("DB migration error: " . $e->getMessage());
    // devam etmeye çalış
}

// Helper: güvenli dosya uzantısı alma
function getSafeExtension($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return strtolower($ext);
}

// Allowed types
$allowedExtensions = ['pdf', 'doc', 'docx', 'rtf', 'txt'];
$allowedMimePrefixes = ['application', 'text']; // ek güvenlik için dosya tipi kontrolü

// Max file size (bytes) örn 5 MB
$maxFileSize = 5 * 1024 * 1024;

// Hedef yükleme dizini
$uploadDir = __DIR__ . '/../uploads/cvs/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Basit routing: eğer bir dosya yüklendiyse CV kaydı yap, değilse ileti formu kaydet
if (!empty($_FILES['cv_file']) && $_FILES['cv_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    // CV gönderimi
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');

    // Basit doğrulama
    if ($name === '' || $email === '') {
        http_response_code(400);
        echo "Ad ve e-posta zorunludur.";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Geçerli bir e-posta adresi giriniz.";
        exit;
    }

    $file = $_FILES['cv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo "Dosya yüklenirken bir hata oluştu. Hata kodu: " . $file['error'];
        exit;
    }

    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo "Dosya çok büyük. Maksimum " . ($maxFileSize / 1024 / 1024) . " MB.";
        exit;
    }

    // MIME tipi tespiti
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

    // Uzantı kontrolü
    $originalFilename = $file['name'];
    $ext = getSafeExtension($originalFilename);
    if (!in_array($ext, $allowedExtensions)) {
        http_response_code(400);
        echo "İzin verilmeyen dosya türü. Yalnızca: " . implode(', ', $allowedExtensions);
        exit;
    }

    // MIME tipi basit kontrol (daha ileri güvenlik için içeriğe bakılabilir)
    $mimeBase = explode('/', $mimeType)[0];
    if (!in_array($mimeBase, $allowedMimePrefixes)) {
        // pdf gibi durumlarda application/pdf => application prefix'i var; text/plain => text
        // İstisna: application/pdf izin veriliyor via prefix application
        // Eğer isterseniz burada application/pdf'e özel izin verin.
        // (şu an application ve text prefix'lerine izin veriyoruz)
    }

    // Benzersiz dosya adı oluştur
    $storedFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $uploadDir . $storedFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo "Dosya sunucuya kaydedilemedi.";
        exit;
    }

    // Veritabanına kaydet
    try {
        $stmt = $pdo->prepare("INSERT INTO candidates (name, email, phone, expertise, stored_filename, original_filename, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $expertise,
            $storedFilename,
            $originalFilename,
            (int)$file['size'],
            $mimeType
        ]);
    } catch (Exception $e) {
        error_log("DB insert candidate error: " . $e->getMessage());
        // Hata halinde dosyayı sil
        if (file_exists($destination)) unlink($destination);
        http_response_code(500);
        echo "Veritabanına kaydedilemedi.";
        exit;
    }

    // Opsiyonel: e-posta gönderimi (sunucuda mail() yapılandırması varsa)
    // $to = $_POST['to_email'] ?? null;
    // if ($to) { mail(...); }

    // Başarılı yanıt
    echo "<h2>CV'niz başarıyla alındı.</h2>";
    echo "<p>İsim: " . htmlspecialchars($name) . "</p>";
    echo "<p>E‑posta: " . htmlspecialchars($email) . "</p>";
    echo "<p>Telefon: " . htmlspecialchars($phone) . "</p>";
    echo "<p>Uzmanlık: " . htmlspecialchars($expertise) . "</p>";
    echo "<p>Dosya: " . htmlspecialchars($originalFilename) . "</p>";
    exit;
} elseif (!empty($_POST['subject']) || !empty($_POST['message'])) {
    // contact form (index.html contact bölümü) bu endpoint'e POST ediyor; isterseniz ayrı yapabilirsiniz.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '') {
        http_response_code(400);
        echo "Ad ve e-posta zorunludur.";
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
    } catch (Exception $e) {
        error_log("DB insert message error: " . $e->getMessage());
        http_response_code(500);
        echo "Mesajınız kaydedilemedi.";
        exit;
    }

    echo "<h2>Mesajınız başarıyla gönderildi. Teşekkürler!</h2>";
    exit;
} else {
    http_response_code(400);
    echo "Beklenmeyen istek.";
    exit;
}
?>