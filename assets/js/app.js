// Theme cycling: system -> light -> dark, persisted in localStorage.
(function () {
  var KEY = 'aifin-theme';
  var labels = { system: 'ตามระบบ', light: 'โหมดสว่าง', dark: 'โหมดมืด' };
  var order = ['system', 'light', 'dark'];
  // Monochrome SVG icons that inherit the button's text colour.
  var svg = function (paths) {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
      'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" ' +
      'style="display:block">' + paths + '</svg>';
  };
  var icons = {
    light: svg('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'),
    dark: svg('<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>'),
    system: svg('<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>')
  };

  function apply(theme) {
    var el = document.documentElement;
    if (theme === 'system') el.removeAttribute('data-theme');
    else el.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-label]').forEach(function (b) {
      b.innerHTML = icons[theme] || icons.system;
    });
    // Keep the Thai label available to screen readers / as a tooltip.
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      var text = labels[theme] || labels.system;
      btn.setAttribute('title', text);
      btn.setAttribute('aria-label', 'สลับธีม: ' + text);
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
