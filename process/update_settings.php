<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

// Banner folder
$logo_folder = dirname(__DIR__) . '/assets/img/';
if (!is_dir($logo_folder)) {
    mkdir($logo_folder, 0777, true);
}

// Helper to upload/overwrite image
function uploadLogoImage($file) {
    global $logo_folder;
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return null;
        }

        // Always save with same filename (overwrite if exists)
        $filename = 'logo.' . $ext;
        $target = $logo_folder . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename; // Only filename (e.g. logo.png)
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name_bn     = $_POST['site_name_bn'] ?? '';
    $site_name_en     = $_POST['site_name_en'] ?? '';
    $registration_no  = $_POST['registration_no'] ?? '';
    $address          = $_POST['address'] ?? '';
    $email            = $_POST['email'] ?? '';
    $phone1           = $_POST['phone1'] ?? '';
    $phone2           = $_POST['phone2'] ?? '';

    // ✅ Strip HTML tags
    $about_text    = isset($_POST['about_text']) ? strip_tags($_POST['about_text']) : '';
    $about_text_en = isset($_POST['about_text_en']) ? strip_tags($_POST['about_text_en']) : '';
    $slogan_bn     = isset($_POST['slogan_bn']) ? strip_tags($_POST['slogan_bn']) : '';
    $slogan_en     = isset($_POST['slogan_en']) ? strip_tags($_POST['slogan_en']) : '';
    $ac_title      = isset($_POST['ac_title']) ? strip_tags($_POST['ac_title']) : '';
    $ac_no         = isset($_POST['ac_no']) ? strip_tags($_POST['ac_no']) : '';
    $bank_name     = isset($_POST['bank_name']) ? strip_tags($_POST['bank_name']) : '';
    $bank_address  = isset($_POST['bank_address']) ? strip_tags($_POST['bank_address']) : '';

    // Check if a new logo is uploaded
    $new_logo = uploadLogoImage($_FILES['profile_image']);
    if ($new_logo) {
        $logo = $new_logo; // Use the new logo
    } else {
        // Retrieve the existing logo from the database
        $stmt = $pdo->query("SELECT logo FROM setup WHERE id=1");
        $logo = $stmt->fetchColumn(); // Use the existing logo
    }

    $objectives       = $_POST['objectives'] ?? '';
    $facebook         = $_POST['facebook'] ?? '';
    $youtube          = $_POST['youtube'] ?? '';
    $linkedin         = $_POST['linkedin'] ?? '';
    $instagram        = $_POST['instagram'] ?? '';

    // ✅ Update DB
    $stmt = $pdo->prepare("
        UPDATE setup 
        SET site_name_bn=?, site_name_en=?, registration_no=?, address=?, email=?, phone1=?, phone2=?, 
            about_text=?, about_text_en=?, slogan_bn=?, slogan_en=?, ac_title=?, ac_no=?, logo=?,
            objectives=?, facebook=?, youtube=?, linkedin=?, instagram=?, bank_name=?, bank_address=?
        WHERE id=1
    ");

    $stmt->execute([
        $site_name_bn,
        $site_name_en,
        $registration_no,
        $address,
        $email,
        $phone1,
        $phone2,
        $about_text,
        $about_text_en,
        $slogan_bn,
        $slogan_en,
        $ac_title,
        $ac_no,
        $logo,
        $objectives,
        $facebook,
        $youtube,
        $linkedin, 
        $instagram,
        $bank_name,
        $bank_address
    ]);

    $_SESSION['success_msg'] = "✅ Setup updated successfully..! (সফলভাবে হালনাগাদ করা হলো..!)";
    header('Location: ../admin/index.php');
    exit;
} else {
    header('Location: ../admin/index.php');
    exit;
}
