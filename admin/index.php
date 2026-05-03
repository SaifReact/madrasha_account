<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

$stmt = $pdo->query("SELECT * FROM setup LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/open.php'; ?>
<?php include_once __DIR__ . '/../includes/side_bar.php'; ?>

<!-- Hero Start -->      
    <main class="col-12 col-md-10 col-lg-10 col-xl-10 px-md-3">
            <div class="row px-2">
                <div class="card shadow-lg rounded-3 border-0">
                    <div class="card-body p-4">
                      <h3 class="mb-3 text-primary fw-bold">System Setting <span class="text-secondary">( সিস্টেম সেটিং )</span></h3> 
                      <hr class="mb-4" />

                        <form method="post" enctype="multipart/form-data" action="../process/update_settings.php">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="site_name_bn" class="form-label">Site Name (Bangla)</label>
                                    <input type="text" class="form-control" id="site_name_bn" name="site_name_bn" value="<?= htmlspecialchars($settings['site_name_bn'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="site_name_en" class="form-label">Site Name (English)</label>
                                    <input type="text" class="form-control" id="site_name_en" name="site_name_en" value="<?= htmlspecialchars($settings['site_name_en'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="slogan_bn" class="form-label">Slogan (Bangla)</label>
                                    <input type="text" class="form-control" id="slogan_bn" name="slogan_bn" value="<?= htmlspecialchars($settings['slogan_bn'] ?? '') ?>" >
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="slogan_en" class="form-label">Slogan (English)</label>
                                    <input type="text" class="form-control" id="slogan_en" name="slogan_en" value="<?= htmlspecialchars($settings['slogan_en'] ?? '') ?>" >
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="ac_title" class="form-label">A/C Title</label>
                                    <input type="text" class="form-control" id="ac_title" name="ac_title" value="<?= htmlspecialchars($settings['ac_title'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="ac_no" class="form-label">A/C No.</label>
                                    <input type="text" class="form-control" id="ac_no" name="ac_no" value="<?= htmlspecialchars($settings['ac_no'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="bank_address" class="form-label">Bank Address</label>
                                    <input type="text" class="form-control" id="bank_address" name="bank_address" value="<?= htmlspecialchars($settings['bank_address'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="registration_no" class="form-label">Registration No</label>
                                    <input type="text" class="form-control" id="registration_no" name="registration_no" value="<?= htmlspecialchars($settings['registration_no'] ?? '') ?>">
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="phone1" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone1" name="phone1" value="<?= htmlspecialchars($settings['phone1'] ?? '') ?>">
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="phone2" class="form-label">Mobile</label>
                                    <input type="text" class="form-control" id="phone2" name="phone2" value="<?= htmlspecialchars($settings['phone2'] ?? '') ?>">
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                   <div class="row">
                                    <div class="col-md-9">
                                    <label for="profile_image" class="form-label">
                                        Site Logo <span class="text-secondary small">(ছবি নির্বাচন করুন)</span>
                                    </label>
                                    <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">
                                    <span id="profileImageError" class="text-danger small"></span>
                                </div>
                                <div class="col-md-3 d-flex justify-content-center align-items-center position-relative" style="min-height: 90px;">
                                    <?php if (!empty($settings['logo'])): ?>
                                        <img id="imagePreview" 
                                            src="../assets/img/<?= htmlspecialchars($settings['logo']) ?>" 
                                            alt="Logo Preview" 
                                            style="max-width: 200px; max-height: 75px; border-radius: 5px; box-shadow: 0 2px 8px #0002; background: #fff; padding: 6px;" />
                                        <button type="button" id="profileImgClear" 
                                                class="btn-close" 
                                                style="position:absolute; top:8px; right:8px; background:#d33; opacity:0.8; width:18px; height:18px; padding:2px; border-radius:50%; z-index:2;" 
                                                tabindex="-1" title="Clear Image"></button>
                                    <?php else: ?>
                                        <img id="imagePreview" 
                                            src="#" 
                                            alt="Image Preview" 
                                            style="display:none; max-width: 200px; max-height: 75px; border-radius: 5px; box-shadow: 0 2px 8px #0002; background: #fff; padding: 6px;" />
                                        <button type="button" id="profileImgClear" 
                                                class="btn-close" 
                                                style="display:none; position:absolute; top:8px; right:8px; background:#d33; opacity:0.8; width:18px; height:18px; padding:2px; border-radius:50%; z-index:2;" 
                                                tabindex="-1" title="Clear Image"></button>
                                    <?php endif; ?>
                                </div>
                             </div>
                        </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="about_text" class="form-label">About (Bangla)</label>
                                    <textarea class="form-control" id="about_text" name="about_text" rows="5"><?= htmlspecialchars($settings['about_text'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="about_text_en" class="form-label">About (English)</label>
                                    <textarea class="form-control" id="about_text_en" name="about_text_en" rows="5"><?= htmlspecialchars($settings['about_text_en'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="objectives" class="form-label">Objectives</label>
                                    <textarea class="form-control" id="objectives" name="objectives" rows="5"><?= htmlspecialchars($settings['objectives'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="facebook" class="form-label">Facebook</label>
                                        <input type="text" class="form-control" id="facebook" name="facebook" value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="youtube" class="form-label">Youtube</label>
                                        <input type="text" class="form-control" id="youtube" name="youtube" value="<?= htmlspecialchars($settings['youtube'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="linkedin" class="form-label">Linkedin</label>
                                        <input type="text" class="form-control" id="linkedin" name="linkedin" value="<?= htmlspecialchars($settings['linkedin'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="instagram" class="form-label">Instagram</label>
                                        <input type="text" class="form-control" id="instagram" name="instagram" value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
                                        Update Setup ( সেটআপ হালনাগাদ করুন )
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
  </div>
</div>
<!-- Hero End -->

<script src="https://cdn.ckeditor.com/ckeditor5/41.2.0/classic/ckeditor.js"></script>
<script>
ClassicEditor.create(document.querySelector('#about_text'), {
    toolbar: ['bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
}).catch(error => {});

ClassicEditor.create(document.querySelector('#about_text_en'), {
    toolbar: ['bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
}).catch(error => {});

ClassicEditor.create(document.querySelector('#objectives'), {
    toolbar: ['bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
}).catch(error => {});
</script>

<?php include_once __DIR__ . '/../includes/end.php'; ?>
