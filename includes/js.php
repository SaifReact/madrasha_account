<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var agree = document.getElementById('agreeRules');
        var agreeOfr = document.getElementById('agreeOffer');
        var goBtn = document.getElementById('goToFormBtn');
        agree.addEventListener('change', function() {
            goBtn.style.display = this.checked ? 'inline-block' : 'none';
        });
        goBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Pass checkbox value to member_form.php as query param
            var agreeVal = agree.checked ? 1 : 0;
            var offerVal = agreeOfr.checked ? 1 : 0;
            var agreeValB64 = btoa(agreeVal.toString());
            var offerValB64 = btoa(offerVal.toString());
            window.location.href = 'forms.php?agreed=' + agreeValB64 + '&offer=' + offerValB64;
        });
    });

    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('imagePreview');
        const clearBtn = document.getElementById('profileImgClear');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                if (clearBtn) clearBtn.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
            if (clearBtn) clearBtn.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const clearBtn = document.getElementById('profileImgClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                const preview = document.getElementById('imagePreview');
                const input = document.getElementById('profile_image');
                preview.src = '#';
                preview.style.display = 'none';
                this.style.display = 'none';
                input.value = ''; // Clear the file input
            });
        }
    });

    // Profile image validation: .jpg, .jpeg, .png only, max 1MB, and preview only if valid
    document.getElementById('profile_image').addEventListener('change', function(e) {
    var input = e.target;
    var errorSpan = document.getElementById('profileImageError');
    var preview = document.getElementById('imagePreview');
    var clearBtn = document.getElementById('profileImgClear');
    errorSpan.textContent = '';
    preview.style.display = 'none';
    clearBtn.style.display = 'none';
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    var ext = file.name.split('.').pop().toLowerCase();
    var allowedExts = ['jpg', 'jpeg', 'png'];
    if (!allowedTypes.includes(file.type) || !allowedExts.includes(ext)) {
        errorSpan.textContent = 'শুধুমাত্র .jpg, .jpeg, .png ফরম্যাটের ছবি দিন (Only .jpg, .jpeg, .png allowed)';
        input.value = '';
        return;
    }
    if (file.size > 1024 * 1024) {
        errorSpan.textContent = '১MB এর বেশি ছবি আপলোড করা যাবে না (Max 1MB allowed)';
        input.value = '';
        return;
    }
    // If valid, show preview
    var reader = new FileReader();
    reader.onload = function(ev) {
        preview.src = ev.target.result;
        preview.style.display = 'block';
        clearBtn.style.display = 'block';
    }
    reader.readAsDataURL(file);
    errorSpan.textContent = '';
    });

    // Function to NID validation message
    function validateNID() {
        var nid = document.getElementById('nid').value;
        var errorSpan = document.getElementById('nidError');
        if (nid.length > 0 && nid.length !== 10 && nid.length !== 17 && nid.length !== 19) {
            if (nid.length > 10 && nid.length < 17 && nid.length < 19) {
            errorSpan.textContent = 'শুধুমাত্র ১০, ১৭, ১৯ সংখ্যার এনআইডি/জন্ম নিবন্ধন নম্বর লিখুন (Enter only 10, 17, 19 digit NID/BRN number)';
            } else {
            errorSpan.textContent = '';
            }
        } else {
            errorSpan.textContent = '';
        }
        // Prevent non-numeric input
        document.getElementById('nid').value = nid.replace(/[^0-9]/g, '');
        }
        
        function clearNIDError() {
        document.getElementById('nidError').textContent = '';
        }

        // DOB validation: must be at least 18 years old
        function validateDOB() {
            var dobInput = document.getElementById('dob');
            var errorSpan = document.getElementById('dobError');
            var dob = dobInput.value;
            if (!dob) {
                errorSpan.textContent = '';
                return;
            }
            var dobDate = new Date(dob);
            var today = new Date();
            var age = today.getFullYear() - dobDate.getFullYear();
            var m = today.getMonth() - dobDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dobDate.getDate())) {
                age--;
            }
            if (age < 18) {
                errorSpan.textContent = 'বয়স অবশ্যই ১৮ বছরের বেশি হতে হবে (Must be at least 18 years old)';
            } else {
                errorSpan.textContent = '';
            }
        }
        function clearDOBError() {
            document.getElementById('dobError').textContent = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            var dobInput = document.getElementById('dob');
            if (dobInput) {
                dobInput.addEventListener('change', validateDOB);
                dobInput.addEventListener('input', validateDOB);
                dobInput.addEventListener('focus', clearDOBError);
            }
        });

        function validateNameEn() {
            var input = document.getElementById('name_en');
            var errorSpan = document.getElementById('nameEnError');
            // Convert to uppercase
            input.value = input.value.toUpperCase();
            // Only allow English letters and spaces
            var valid = /^[A-Z ]*$/.test(input.value);
            if (!valid) {
                errorSpan.textContent = 'শুধুমাত্র ইংরেজি বড় হাতের অক্ষর লিখুন (Only uppercase English letters allowed)';
                // Remove invalid characters
                input.value = input.value.replace(/[^A-Z ]/g, '');
            } else {
                errorSpan.textContent = '';
            }
            }
            function clearNameEnError() {
            document.getElementById('nameEnError').textContent = '';
            }

    // Function to Mobile validation message
        function validateMobile() {
        var mobile = document.getElementById('mobile').value;
        var errorSpan = document.getElementById('mobileError');
        // Only allow numbers
        document.getElementById('mobile').value = mobile.replace(/[^0-9]/g, '');
        mobile = document.getElementById('mobile').value;
        var allowedPrefixes = ['017', '014', '019', '018', '016', '015', '044', '096'];
        var prefix = mobile.substring(0, 3);
        if (mobile.length === 0) {
            errorSpan.textContent = '';
            return;
        }
        if (mobile.length !== 11) {
            errorSpan.textContent = '১১ সংখ্যার মোবাইল নম্বর লিখুন (Enter 11 digit mobile number)';
            return;
        }
        if (!allowedPrefixes.includes(prefix)) {
            errorSpan.textContent = 'মোবাইল নম্বরটি 017, 014, 019, 018, 016, 015, 044, 096 দিয়ে শুরু হতে হবে (Must start with allowed prefix)';
            return;
        }
        errorSpan.textContent = '';
        }
        function clearMobileError() {
        document.getElementById('mobileError').textContent = '';
        }    
        
        // Function to Share validation message

        function validateShare() {
        const shareInput = document.getElementById('share');
        const errorSpan = document.getElementById('shareError');
    
        // Remove non-numeric characters except when user is typing
        let val = shareInput.value.replace(/[^0-9]/g, '');
        // Only allow up to 3 digits (optional, remove if not needed)
        // val = val.slice(0, 3);
        shareInput.value = val;

        // Only validate if not empty
        if (val.length > 0) {
            const share = Number(val);
            if (share < 2) {
                errorSpan.textContent = 'শেয়ার সংখ্যা অবশ্যই ২ এর সমান বা বেশি হতে হবে (The number of shares must be equal to or greater than 2)';
            } else {
                errorSpan.textContent = '';
            }
        } else {
            errorSpan.textContent = '';
        }
        }
    
    function clearSHAREError() {
        document.getElementById('shareError').textContent = '';
    }

    // Nominee card template
    function getNomineeCard(idx) {
        // Only show remove button for cards after the first
        const removeBtn = idx === 0
            ? '<button type="button" class="btn-close position-absolute top-0 end-0 m-2" title="Cannot remove first nominee" tabindex="-1" style="opacity:0; pointer-events:none;"></button>'
            : '<button type="button" class="btn-close position-absolute top-0 end-0 m-2 removeNomineeBtn" title="Remove Nominee" tabindex="-1"></button>';
        return `
        <div class="mb-2 nominee-card position-relative" style="box-shadow:0 1px 8px #b85c3811;">
            <div>
                ${removeBtn}
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2 ">
                            <label class="form-label">জাতীয় পরিচয়পত্র/জন্ম নিবন্ধন নম্বর <span class="text-secondary small">(NID/BRN Number)</span></label>
                            <input type="text" name="nominee_nid[]" class="form-control rounded-pill nominee-nid-input" required maxlength="17" autocomplete="off" oninput="validateNomineeNID(this)" onfocus="clearNomineeNIDError(this)">
                            <span class="text-danger small nominee-nid-error"></span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">নাম <span class="text-secondary small">(Name)</span></label>
                            <input type="text" name="nominee_name[]" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">শতকরা <span class="text-secondary small">(Percentage)</span></label>
                            <input type="number" name="nominee_percent[]" class="form-control rounded-pill" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label">জন্ম তারিখ <span class="text-secondary small">(Date of Birth)</span></label>
                            <input type="date" name="nominee_dob[]" class="form-control rounded-pill" required>
                        </div>    
                        <div class="mb-2 ">
                            <label class="form-label">সম্পর্ক <span class="text-secondary small">(Relationship)</span></label>
                            <input type="text" name="nominee_relation[]" class="form-control rounded-pill" required>
                        </div>                    
                        <div class="mb-2">
                            <label for="nominee_image" class="form-label">নমিনীর ছবি <span class="text-secondary small">(Nominee Photo)</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="file" name="nominee_image[]" accept="image/*" class="form-control form-control-md nominee-img-input" style="margin-top:2px; max-width:280px;">
                                <div class="position-relative d-inline-block nominee-img-preview-wrapper">
                                    <img src="#" alt="Preview" class="img-thumbnail nominee-img-preview" style="display:none; max-width:60px; max-height:60px; border-radius:8px; background:#fff; margin-bottom:6px;" />
                                    <button type="button" class="btn-close nominee-img-clear" style="display:none; position:absolute; top:1px; right:1px; background:#d33; opacity:0.8; width:10px; height:10px; padding:2px; border-radius:50%; z-index:2;" tabindex="-1" title="Clear Image"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>`;
    }

     // Add nominee card
    function addNomineeCard() {
        const section = document.getElementById('nomineeSection');
        const idx = section.querySelectorAll('.nominee-card').length;
        const div = document.createElement('div');
        div.innerHTML = getNomineeCard(idx);
        section.appendChild(div.firstElementChild);
    }

    // Remove nominee card
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeNomineeBtn')) {
            // Only allow removing if not the first nominee card
            const section = document.getElementById('nomineeSection');
            const cards = Array.from(section.querySelectorAll('.nominee-card'));
            const card = e.target.closest('.nominee-card');
            if (cards.indexOf(card) > 0) {
                card.remove();
            }
        }
    });

    // Function to validate nominee NID
    // Nominee NID validation
    function validateNomineeNID(input) {
        var nid = input.value;
        var errorSpan = input.parentElement.querySelector('.nominee-nid-error');
        if (nid.length > 0 && nid.length !== 10 && nid.length !== 17) {
            if (nid.length > 10 && nid.length < 17) {
                errorSpan.textContent = 'শুধুমাত্র ১০ অথবা ১৭ সংখ্যার এনআইডি নম্বর লিখুন (Enter only 10 or 17 digit NID number)';
            } else if (nid.length > 17) {
                errorSpan.textContent = 'সর্বাধিক ১৭ সংখ্যার এনআইডি নম্বর লিখতে পারবেন (Maximum 17 digits allowed)';
            } else {
                errorSpan.textContent = '';
            }
        } else {
            errorSpan.textContent = '';
        }
        // Prevent non-numeric input
        input.value = nid.replace(/[^0-9]/g, '');
    }
    function clearNomineeNIDError(input) {
        var errorSpan = input.parentElement.querySelector('.nominee-nid-error');
        errorSpan.textContent = '';
    }

    // Image preview for nominee
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('nominee-img-input')) {
            const input = e.target;
            const preview = input.closest('.nominee-card').querySelector('.nominee-img-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }
    });

    // Add first nominee card on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('addNomineeBtn').addEventListener('click', addNomineeCard);
        addNomineeCard();
    });

    // Image preview for nominee
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('nominee-img-input')) {
            const input = e.target;
            const card = input.closest('.nominee-card');
            const preview = card.querySelector('.nominee-img-preview');
            const clearBtn = card.querySelector('.nominee-img-clear');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                    clearBtn.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
                clearBtn.style.display = 'none';
            }
        }
    });

    // Clear nominee image preview and file input
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('nominee-img-clear')) {
            const card = e.target.closest('.nominee-card');
            const preview = card.querySelector('.nominee-img-preview');
            const input = card.querySelector('.nominee-img-input');
            preview.src = '#';
            preview.style.display = 'none';
            e.target.style.display = 'none';
            // Clear file input
            input.value = '';
        }
    });

        document.addEventListener('DOMContentLoaded', function() {
            const maritalStatus = document.getElementById('marital_status');
            const spouseNameGroup = document.getElementById('spouse_name_group');
            maritalStatus.addEventListener('change', function() {
                spouseNameGroup.style.display = this.value === 'Married' ? 'block' : 'none';
            });
        });

        // Prevent nominee_percent[] sum > 100 and max 100 inputs
            function validateNomineePercentSum() {
                var percentInputs = document.querySelectorAll('input[name="nominee_percent[]"]');
                var total = 0;
                percentInputs.forEach(function(input) {
                    var val = parseFloat(input.value) || 0;
                    total += val;
                });
                // Show error if sum > 100
                var formError = document.getElementById('formErrorMsg');
                if (total > 100) {
                    formError.textContent = 'সর্বমোট শতকরা মান ১০০ এর বেশি হতে পারবে না (Total percentage cannot exceed 100)';
                    formError.style.display = 'block';
                    return false;
                } else {
                    formError.style.display = 'none';
                    return true;
                }
            }

            // Attach validation to all nominee_percent[] inputs
            document.addEventListener('input', function(e) {
                if (e.target && e.target.name === 'nominee_percent[]') {
                    validateNomineePercentSum();
                }
            });

            // Prevent more than 100 nominee_percent[] inputs
            function checkNomineeInputLimit() {
                var percentInputs = document.querySelectorAll('input[name="nominee_percent[]"]');
                if (percentInputs.length > 100) {
                    var formError = document.getElementById('formErrorMsg');
                    formError.textContent = '১০০টির বেশি নমিনি যোগ করা যাবে না (Cannot add more than 100 nominees)';
                    formError.style.display = 'block';
                    return false;
                }
                return true;
            }

            // When adding a nominee card, check the limit
            var addNomineeBtn = document.getElementById('addNomineeBtn');
            if (addNomineeBtn) {
                addNomineeBtn.addEventListener('click', function(e) {
                    if (!checkNomineeInputLimit()) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

        document.querySelector('form').addEventListener('submit', function(e) {
        // Check for visible error messages
        var errors = [
            document.getElementById('nidError'),
            document.getElementById('mobileError'),
            document.getElementById('dobError'),
            document.getElementById('profileImageError'),
            document.getElementById('nameEnError')
        ];
        var hasError = false;
        errors.forEach(function(span) {
            if (span && span.textContent.trim() !== '') {
            hasError = true;
            }
        });
        // Check nominee_nid[] errors
        var nomineeNidErrors = document.querySelectorAll('.nominee-nid-error');
        nomineeNidErrors.forEach(function(span) {
            if (span && span.textContent.trim() !== '') {
            hasError = true;
            }
        });
        // Check required fields
        var requiredFields = [
          {id: 'nid', error: 'nidError', msg: 'এনআইডি/জন্ম নিবন্ধন নম্বর অবশ্যই দিতে হবে (NID/BRN is required)'},
          {id: 'dob', error: 'dobError', msg: 'জন্ম তারিখ অবশ্যই দিতে হবে (Date of Birth is required)'},
          {id: 'name_en', error: 'nameEnError', msg: 'ইংরেজি নাম অবশ্যই দিতে হবে (Name in English is required)'},
          {id: 'share', error: null, msg: 'শেয়ার সংখ্যা অবশ্যই দিতে হবে (Share is required)'},
          {id: 'mobile', error: 'mobileError', msg: 'মোবাইল নম্বর অবশ্যই দিতে হবে (Mobile number is required)'},
          {id: 'username', error: null, msg: 'ইউজারনেম অবশ্যই দিতে হবে (Username is required)'},
          {id: 'password', error: null, msg: 'পাসওয়ার্ড অবশ্যই দিতে হবে (Password is required)'},
          {id: 'retype_password', error: null, msg: 'পুনরায় পাসওয়ার্ড অবশ্যই দিতে হবে (Retype Password is required)'}
        ];
        requiredFields.forEach(function(f) {
          var el = document.getElementById(f.id);
          if (!el || !el.value.trim()) {
            hasError = true;
            if (f.error) {
              var errSpan = document.getElementById(f.error);
              if (errSpan) errSpan.textContent = f.msg;
            } else {
              // For fields without error span, show in formErrorMsg
              var formError = document.getElementById('formErrorMsg');
              formError.textContent = f.msg;
              formError.style.display = 'block';
            }
          }
        });
        // Check profile_image required
        var profileInput = document.getElementById('profile_image');
        var profileError = document.getElementById('profileImageError');
        if (!profileInput.value) {
            profileError.textContent = 'প্রোফাইল ছবি অবশ্যই দিতে হবে (Profile image is required)';
            hasError = true;
        }

        var percentInputs = document.querySelectorAll('input[name="nominee_percent[]"]');
    if (percentInputs.length > 100) {
        var formError = document.getElementById('formErrorMsg');
        formError.textContent = '১০০টির বেশি নমিনি যোগ করা যাবে না (Cannot add more than 100 nominees)';
        formError.style.display = 'block';
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }
    var total = 0;
    percentInputs.forEach(function(input) {
        var val = parseFloat(input.value) || 0;
        total += val;
    });
    if (total > 100) {
        var formError = document.getElementById('formErrorMsg');
        formError.textContent = 'সর্বমোট শতকরা মান ১০০ এর বেশি হতে পারবে না (Total percentage cannot exceed 100)';
        formError.style.display = 'block';
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }
        if (hasError) {
            var formError = document.getElementById('formErrorMsg');
            formError.textContent = 'ডাটা প্রদানে ভুল আছে, দয়া করে সংশোধন করুন (There are errors in the form, please fix them)';
            formError.style.display = 'block';
            e.preventDefault();F
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return false;
        } else {
            var formError = document.getElementById('formErrorMsg');
            formError.style.display = 'none';
        }
        });
    </script>