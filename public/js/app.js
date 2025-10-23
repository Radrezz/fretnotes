document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");

  // Default tampilkan form login
  loginForm.style.display = "block";
  registerForm.style.display = "none";

  // Tombol untuk beralih ke form login
  document.getElementById("login-tab").addEventListener("click", function () {
    loginForm.style.display = "block";
    registerForm.style.display = "none";
  });

  // Tombol untuk beralih ke form register
  document.getElementById("register-tab").addEventListener("click", function () {
    loginForm.style.display = "none";
    registerForm.style.display = "block";
  });
});
