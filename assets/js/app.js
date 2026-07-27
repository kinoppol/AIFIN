// Theme cycling: system -> light -> dark, persisted in localStorage.
(function () {
  var KEY = 'aifin-theme';
  var labels = { system: 'ตามระบบ', light: 'โหมดสว่าง', dark: 'โหมดมืด' };
  var order = ['system', 'light', 'dark'];

  function apply(theme) {
    var el = document.documentElement;
    if (theme === 'system') el.removeAttribute('data-theme');
    else el.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-label]').forEach(function (b) {
      b.textContent = labels[theme] || labels.system;
    });
  }

  function current() {
    return localStorage.getItem(KEY) || 'system';
  }

  window.__cycleTheme = function () {
    var i = order.indexOf(current());
    var next = order[(i + 1) % order.length];
    localStorage.setItem(KEY, next);
    apply(next);
  };

  apply(current());
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-theme-toggle]');
    if (t) { e.preventDefault(); window.__cycleTheme(); }
  });
})();

// Redeem calculator: units * unitDays = days preview.
document.addEventListener('input', function (e) {
  var el = e.target;
  if (el.matches('[data-redeem-units]')) {
    var days = (parseInt(el.value, 10) || 0) * (parseInt(el.getAttribute('data-unit-days'), 10) || 30);
    var out = document.querySelector('[data-redeem-days]');
    if (out) out.textContent = days.toLocaleString();
  }
});
