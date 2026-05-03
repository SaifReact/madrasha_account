<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../config/config.php';

// Access specific data from session
$siteName = $_SESSION['setup']['site_name_bn'] ?? '';
$reg_no    = $_SESSION['setup']['registration_no'] ?? '';
$address    = $_SESSION['setup']['address'] ?? '';
$phone1    = $_SESSION['setup']['phone1'] ?? '';
$phone2    = $_SESSION['setup']['phone2'] ?? '';
$phone = $phone1 . ($phone2 ? ', ' . $phone2 : '');
$email   = $_SESSION['setup']['email'] ?? '';
$slogan_bn = $_SESSION['setup']['slogan_bn'] ?? '';
$slogan_en = $_SESSION['setup']['slogan_en'] ?? ''; 
$slogan = $slogan_bn . ($slogan_en ? ' ( ' . $slogan_en . ' )' : '');

?>

<div class="container py-3">
            <div class="row g-5">
                <div class="col-md-7 col-lg-5 wow fadeIn" data-wow-delay="0.1s">
                    <a href="index.php" class="navbar-brand">
                     <span style="
                display: inline-block;
                font-family: 'Poppins', Arial, sans-serif;
                font-size: .85rem;
                font-weight: 700;
                color: #b85c38;
                letter-spacing: 1.5px;
                text-shadow: 1px 2px 8px #fff8, 0 2px 8px #b85c3822;
                margin: 0.2em 0;
            ">
                <span style="vertical-align:middle;"><?= htmlspecialchars($siteName); ?></span>
            </span>
                </a>
                    <p class="mb-0">নিবন্ধন নং- <?= htmlspecialchars($reg_no); ?></p>
                    <p class="mb-0"><?= htmlspecialchars($slogan); ?></p>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.3s">
                    <h5 class="text-white mb-4">Get In Touch (যোগাযোগ করুন)</h5>
                    <p><i class="fa fa-map-marker-alt me-3"></i><?= htmlspecialchars($address); ?></p>
                    <p><i class="fa fa-phone-alt me-3"></i><?= htmlspecialchars($phone); ?></p>
                    <p><i class="fa fa-envelope me-3"></i><?= htmlspecialchars($email); ?></p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-linkedin-in"></i></a>
                        <a class="btn btn-outline-primary btn-square border-2 me-2" href="#!"><i
                                class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="0.5s">
                    <h5 class="text-white mb-4">Quick Link (জনপ্রিয় লিঙ্ক)</h5>
                    <a class="btn btn-link" href="index.php">Home (প্রচ্ছদ)</a>
                    <a class="btn btn-link" href="form.php">Registration (নিবন্ধন)</a>
                    <a class="btn btn-link" href="login.php">Login (লগইন)</a>
                    <a class="btn btn-link" href="contact.php">Contact (যোগাযোগ)</a>
                    <a class="btn btn-link" href="#!">Jobs (চাকরি)</a>
                </div>
            </div>
        </div>
        <div class="container wow fadeIn" data-wow-delay="0.1s">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#!">CPSSL</a>, All Right Reserved.
                        Designed By <a class="border-bottom" href="#">Coder Station</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="#!">Home</a>
                            <a href="#!">Cookies</a>
                            <a href="#!">Help</a>
                            <a href="#!">FAQs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>