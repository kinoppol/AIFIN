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

// --- Styled modal system (replaces native alert/confirm) --------------------
(function () {
  window.AIFIN = window.AIFIN || {};
  var ICON = {
    q: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>',
    warn: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>'
  };

  function open(opts) {
    var danger = !!opts.danger;
    var alertOnly = opts.mode === 'alert';
    var ov = document.createElement('div');
    ov.className = 'modal-ov';
    ov.innerHTML =
      '<div class="modal-box" role="dialog" aria-modal="true">' +
        '<div class="modal-icon ' + (danger ? 'i-danger' : 'i-primary') + '">' + (danger ? ICON.warn : ICON.q) + '</div>' +
        '<h3 class="modal-title"></h3><p class="modal-msg"></p>' +
        '<div class="modal-actions">' +
          (alertOnly ? '' : '<button type="button" class="btn btn-ghost" data-cancel></button>') +
          '<button type="button" class="btn ' + (danger ? 'btn-danger' : 'btn-primary') + '" data-ok></button>' +
        '</div>' +
      '</div>';
    ov.querySelector('.modal-title').textContent = opts.title || 'ยืนยันการทำรายการ';
    ov.querySelector('.modal-msg').textContent = opts.message || '';
    ov.querySelector('[data-ok]').textContent = opts.confirmText || (alertOnly ? 'ตกลง' : 'ยืนยัน');
    var cancelBtn = ov.querySelector('[data-cancel]');
    if (cancelBtn) cancelBtn.textContent = opts.cancelText || 'ยกเลิก';

    document.body.appendChild(ov);
    requestAnimationFrame(function () { ov.classList.add('show'); });

    return new Promise(function (resolve) {
      function close(val) {
        ov.classList.remove('show');
        setTimeout(function () { ov.remove(); }, 180);
        document.removeEventListener('keydown', onKey);
        resolve(val);
      }
      function onKey(e) {
        if (e.key === 'Escape') close(false);
        else if (e.key === 'Enter') close(true);
      }
      ov.querySelector('[data-ok]').addEventListener('click', function () { close(true); });
      if (cancelBtn) cancelBtn.addEventListener('click', function () { close(false); });
      ov.addEventListener('click', function (e) { if (e.target === ov && !alertOnly) close(false); });
      document.addEventListener('keydown', onKey);
      ov.querySelector('[data-ok]').focus();
    });
  }

  AIFIN.confirm = function (opts) { return open(typeof opts === 'string' ? { message: opts } : (opts || {})); };
  AIFIN.alert = function (opts) {
    opts = typeof opts === 'string' ? { message: opts } : (opts || {});
    opts.mode = 'alert';
    return open(opts);
  };
})();

// Any form/link with [data-confirm] pops the styled modal before proceeding.
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (form && form.matches && form.matches('[data-confirm]') && form.dataset.confirmed !== '1') {
    e.preventDefault();
    AIFIN.confirm({
      message: form.getAttribute('data-confirm'),
      title: form.getAttribute('data-confirm-title') || undefined,
      confirmText: form.getAttribute('data-confirm-ok') || undefined,
      danger: form.hasAttribute('data-confirm-danger')
    }).then(function (ok) {
      if (ok) { form.dataset.confirmed = '1'; form.submit(); }
    });
  }
}, true);

// Close native <dialog> modals when clicking the backdrop (outside the box).
document.addEventListener('click', function (e) {
  var dlg = e.target;
  if (dlg.tagName === 'DIALOG' && dlg.open) {
    var r = dlg.getBoundingClientRect();
    var inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
    if (!inside) dlg.close();
  }
});

document.addEventListener('click', function (e) {
  var a = e.target.closest && e.target.closest('a[data-confirm]');
  if (a && a.dataset.confirmed !== '1') {
    e.preventDefault();
    AIFIN.confirm({
      message: a.getAttribute('data-confirm'),
      title: a.getAttribute('data-confirm-title') || undefined,
      danger: a.hasAttribute('data-confirm-danger')
    }).then(function (ok) { if (ok) { a.dataset.confirmed = '1'; a.click(); } });
  }
}, true);
