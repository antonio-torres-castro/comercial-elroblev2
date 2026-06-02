(function () {
  var toggle = document.querySelector('.menu-toggle');
  var menu = document.getElementById('mobile-menu');

  if (toggle && menu) {
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      menu.classList.toggle('hidden');
    });

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.add('hidden');
      });
    });
  }

  document.querySelectorAll('.contact-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var note = form.querySelector('.form-note');
      if (note) {
        note.textContent = 'Solicitud registrada para conexión con el flujo comercial.';
      }
    });
  });

  if (window.lucide) {
    window.lucide.createIcons();
  }
}());
