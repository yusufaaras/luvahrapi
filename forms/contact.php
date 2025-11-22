<?php
/**
 * İLETİŞİM FORMU BETİĞİ (forms/contact.php)
 *
 * Bu betik, iletişim formundan gelen verileri alır ve
 * belirtilen adrese (arasy541@gmail.com) gönderir.
 */

// Gönderilecek hedef e-posta adresi
$receiving_email_address = 'arasy541@gmail.com';

if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['subject']) || empty($_POST['message'])) {
    http_response_code(400); // Bad Request
    echo "Lütfen tüm zorunlu alanları doldurunuz.";
    exit;
}

$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
$message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

// E-posta içeriği
$body = "<h2>Yeni İletişim Mesajı</h2>";
$body .= "<p><strong>Gönderen:</strong> " . $name . "</p>";
$body .= "<p><strong>E-posta:</strong> " . $email . "</p>";
$body .= "<p><strong>Konu:</strong> " . $subject . "</p>";
$body .= "<p><strong>Mesaj:</strong></p>";
$body .= "<p>" . nl2br($message) . "</p>";

// E-posta başlıkları (Headers)
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
$headers .= "From: LuvaHr İletişim Formu <no-reply@sitenizin-adi.com>" . "\r\n"; // Kendi alan adınızı kullanın
$headers .= "Reply-To: " . $email . "\r\n";


// E-postayı gönder
$mail_sent = mail($receiving_email_address, $subject, $body, $headers);

if ($mail_sent) {
    echo "OK"; // Başarılı mesajı dön
} else {
    http_response_code(500); // Internal Server Error
    echo "Mesajınız gönderilirken bir hata oluştu. Lütfen tekrar deneyiniz.";
}
?>