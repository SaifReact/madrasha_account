
<script>
// Password Field Toggle and Retyping Validation
        function togglePassword(fieldId, btn) {
            var input = document.getElementById(fieldId);
            var icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordMatch() {
            var pass = document.getElementById('password').value;
            var retype = document.getElementById('retype_password').value;
            var errorSpan = document.getElementById('retypePasswordError');
            var successIcon = document.getElementById('retypePasswordSuccess');
            if (retype.length === 0) {
                errorSpan.textContent = '';
                successIcon.style.display = 'none';
                return;
            }
            if (pass === retype) {
                errorSpan.textContent = '';
                successIcon.style.display = 'inline';
            } else {
                errorSpan.textContent = 'পাসওয়ার্ড মিলছে না (Passwords do not match)';
                successIcon.style.display = 'none';
            }
        }
        function clearPasswordMatchError() {
            document.getElementById('retypePasswordError').textContent = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            var passwordInput = document.getElementById('password');
            var retypeInput = document.getElementById('retype_password');
            if (passwordInput && retypeInput) {
                passwordInput.addEventListener('input', function() {
                    retypeInput.value = '';
                    document.getElementById('retypePasswordSuccess').style.display = 'none';
                    document.getElementById('retypePasswordError').textContent = '';
                });
            }
        });
</script>