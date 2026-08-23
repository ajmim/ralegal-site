// RALegal — script principal (fichier externe pour CSP strict)

// Navigation fixe
const nav = document.getElementById('nav');
const toggle = document.getElementById('navToggle');
const links = document.getElementById('navLinks');

window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
});

toggle.addEventListener('click', () => links.classList.toggle('open'));
links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));

// Formulaire de contact — envoi vers le handler PHP auto-hébergé (contact.php)
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
          alert('Une erreur est survenue. Veuillez nous contacter directement par téléphone.');
        }
      })
      .catch(() => {
        alert('Une erreur est survenue. Veuillez nous contacter directement par téléphone.');
      });
  });
}
