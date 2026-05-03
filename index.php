   <?php include_once __DIR__ . '/config/config.php'; ?>
   <?php include_once __DIR__ . '/includes/open.php'; ?>
   <!-- Hero Start -->
   <div class="container-fluid pb-5 hero-header bg-light">
     <div class="container">
       <div class="row justify-content-center">
         <div class="col-12 col-md-12 col-lg-12 col-xl-6">
           <div class="glass-card">
             <h5 class="text-center fw-bold mb-4" style="color:#045D5D; letter-spacing:1px; text-shadow:1px 2px 8px #fff8; font-size:1.5rem; font-family:'Poppins',sans-serif;">লগইন ( Login )</h5>
             <hr />
             <div class="mb-4">
               <form method="post" action="process/login_process.php">
                 <div class="mb-2">
                   <label for="username" class="form-label">ইউজারনেম <span class="text-secondary small">( Username )</span>
                    </label>
                   <input type="text" class="form-control" id="username" name="username" required autofocus>
                 </div>
                 <div class="mb-4">
                     <label for="password" class="form-label">পাসওয়ার্ড <span class="text-secondary small">( Password )</span>
                     </label>
                     <div class="input-group">
                       <input type="password" class="form-control" id="password" name="password" required>
                       <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)" tabindex="-1">
                         <i class="fa fa-eye"></i>
                       </button>
                     </div>
                 </div>
                 <div class="form-check mt-3">
                   <input class="form-check-input" type="checkbox" value="1" >
                   <label class="form-check-label"> আমাকে মনে রেখো ( Remember Me ) </label>
                 </div>
                 <div class="text-center mt-4">
                   <button type="submit" class="btn btn-success btn-md rounded-pill px-5" style="letter-spacing:1px;">লগইন করতে এগিয়ে যান ( Proceed to Login )</button>
                 </div>
               </form>
             </div>
           </div>
         </div>
       </div>
     </div>
   </div>
   <!-- Hero End --> <?php include_once __DIR__ . '/includes/end.php'; ?>