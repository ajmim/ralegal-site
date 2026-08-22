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

// Formulaire de contact — simulation d'envoi (à connecter à Formspree / backend)
const form = document.getElementById('contactForm');
const success = document.getElementById('formSuccess');

if (form) {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const action = form.getAttribute('action') || '';

    // Envoi réel via Formspree (remplacer YOUR_FORM_ID dans l'attribut action)
    if (action && !action.includes('yourformid')) {
      fetch(action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'Accept': 'application/json' }
      })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(() => {
          success.classList.add('show');
          form.reset();
          setTimeout(() => success.classList.remove('show'), 6000);
        })
        .catch(() => {
          alert('Une erreur est survenue. Veuillez nous contacter directement par téléphone.');
        });
    } else {
      // Démo sans backend
      success.classList.add('show');
      form.reset();
      setTimeout(() => success.classList.remove('show'), 6000);
    }
  });
}
