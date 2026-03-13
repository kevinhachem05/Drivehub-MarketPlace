/* ── panel switching ── */
const loginPanel  = document.querySelector('.panel:not(.signup-panel)');
const signupPanel = document.getElementById('signupPanel');

function showSignup(e) {
  e.preventDefault();
  loginPanel.style.animation  = 'fadeOut 0.3s ease forwards';
  setTimeout(() => {
    loginPanel.style.display = 'none';
    signupPanel.style.display = 'flex';
    signupPanel.style.animation = 'fadeSlide 0.45s cubic-bezier(.22,1,.36,1) both';
  }, 280);
}

function showLogin(e) {
  e.preventDefault();
  signupPanel.style.animation = 'fadeOut 0.3s ease forwards';
  setTimeout(() => {
    signupPanel.style.display = 'none';
    loginPanel.style.display  = 'flex';
    loginPanel.style.animation = 'fadeSlide 0.45s cubic-bezier(.22,1,.36,1) both';
  }, 280);
}

/* ── password toggles ── */
function togglePw() {
  const input = document.getElementById('pwInput');
  const icon  = document.getElementById('eyeIcon');
  toggleEye(input, icon);
}

function togglePwSu() {
  const input = document.getElementById('pwInputSu');
  const icon  = document.getElementById('eyeIconSu');
  toggleEye(input, icon);
}

function toggleEye(input, icon) {
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
  } else {
    input.type = 'password';
    icon.innerHTML = `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>`;
  }
}

