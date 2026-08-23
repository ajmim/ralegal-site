// RALegal — main script (external file for strict CSP)

// Fixed navigation
const nav = document.getElementById('nav');
const toggle = document.getElementById('navToggle');
const links = document.getElementById('navLinks');

window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
});

toggle.addEventListener('click', () => links.classList.toggle('open'));
links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));

// Contact form — posts to the self-hosted PHP handler (contact.php)
const form = document.getElementById('contactForm');
const success = document.getElementById('formSuccess');

if (form) {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const action = form.getAttribute('action') || '/contact.php';

    fetch(action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' }
    })
      .then(r => r.ok ? r.json() : Promise.reject(r.status))
      .then((data) => {
        if (data && data.ok) {
          success.classList.add('show');
          form.reset();
          setTimeout(() => success.classList.remove('show'), 6000);
        } else {
          alert('An error occurred. Please contact us directly by phone.');
        }
      })
      .catch(() => {
        alert('An error occurred. Please contact us directly by phone.');
      });
  });
}
