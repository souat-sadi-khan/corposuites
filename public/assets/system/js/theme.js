$(function () {
  'use strict';

  /* ──────────────────────────────────────────────────
     STATE
  ────────────────────────────────────────────────── */
  var $sidebar   = $('#sidebar');
  var $mainArea  = $('#mainArea');
  var $flyout    = $('#flyoutPanel');
  var collapsed = $('#sidebarCollapsed').val() === 'true'
  var flyTimer   = null;
  var flyHovered = false;

  /* ──────────────────────────────────────────────────
     THEME
  ────────────────────────────────────────────────── */
  var savedTheme = localStorage.getItem('nx-theme') || 'light';
  $('html').attr('data-theme', savedTheme);
  setThemeIcon(savedTheme);

  $('#themeBtn').on('click', function () {
    setMode($('html').attr('data-theme') === 'dark' ? 'light' : 'dark');
  });

  function setMode(next) {

    $('html').attr('data-theme', next);
    localStorage.setItem('nx-theme', next);

    setThemeIcon(next);

    $('#czModeRow .cz-pill').removeClass('active');
    $('#czModeRow .cz-pill[data-mode="' + next + '"]').addClass('active');

    // Sync sidebar with theme
    if (next === 'dark') {
        applySidebarPreset('midnight');
    } else {
        applySidebarPreset('white');
    }
}

  function setThemeIcon (t) {
    $('#themeBtn i').attr('class', t === 'dark' ? 'ri-sun-line' : 'ri-moon-line');
  }

  /* ══════════════════════════════════════════════════
     CUSTOMIZER — sidebar / header / accent / layout
  ══════════════════════════════════════════════════ */
  var root = document.documentElement.style;

  var czSettings = $.extend({
    sidebar      : 'white',
    sidebarCustom: null,
    header       : 'default',
    headerCustom : null,
    accent       : '#4f52e8',
    position     : 'left',
    width        : 'fluid'
  }, JSON.parse(localStorage.getItem('nx-customizer') || '{}'));

  function saveCzSettings () { localStorage.setItem('nx-customizer', JSON.stringify(czSettings)); }

  /* ── Color helpers ── */
  function hexToRgba (hex, alpha) {
    hex = String(hex).replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var num = parseInt(hex, 16);
    return 'rgba(' + ((num >> 16) & 255) + ',' + ((num >> 8) & 255) + ',' + (num & 255) + ',' + alpha + ')';
  }
  function shade (hex, percent) {
    hex = String(hex).replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var num = parseInt(hex, 16);
    var r = (num >> 16) & 255, g = (num >> 8) & 255, b = num & 255;
    function clamp (v) { return Math.min(255, Math.max(0, Math.round(v + (percent / 100) * 255))); }
    r = clamp(r); g = clamp(g); b = clamp(b);
    return '#' + [r, g, b].map(function (v) { return v.toString(16).padStart(2, '0'); }).join('');
  }
  function isLightColor (hex) {
    hex = String(hex).replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var num = parseInt(hex, 16);
    var r = ((num >> 16) & 255) / 255, g = ((num >> 8) & 255) / 255, b = (num & 255) / 255;
    function lin (c) { return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
    return (0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b)) > 0.5;
  }

  /* ── Sidebar palette ── */
  var SB_PRESETS = {
    midnight: { bg: '#0c0d12', light: false },
    slate   : { bg: '#1e2536', light: false },
    ocean   : { bg: '#0a2540', light: false },
    forest  : { bg: '#0f2418', light: false },
    indigo  : { bg: '#1e1b4b', light: false },
    white   : { bg: '#FFFFFF', light: true  }
  };
  function setSidebarPalette (bg, light) {
    var t = light
      ? { tx: 'rgba(15,17,23,.55)', hover: 'rgba(15,17,23,.05)', active: 'rgba(79,82,232,.10)', activeTx: 'var(--accent)', border: 'rgba(15,17,23,.08)', label: 'rgba(15,17,23,.30)', strong: '#0f1117', txHover: 'rgba(15,17,23,.80)', faint: 'rgba(15,17,23,.30)' }
      : { tx: 'rgba(255,255,255,.48)', hover: 'rgba(255,255,255,.06)', active: 'rgba(79,82,232,.20)', activeTx: '#a2a4ff', border: 'rgba(255,255,255,.07)', label: 'rgba(255,255,255,.22)', strong: '#ffffff', txHover: 'rgba(255,255,255,.80)', faint: 'rgba(255,255,255,.24)' };
    root.setProperty('--sb-bg', bg);
    root.setProperty('--sb-tx', t.tx);
    root.setProperty('--sb-hover', t.hover);
    root.setProperty('--sb-active', t.active);
    root.setProperty('--sb-active-tx', t.activeTx);
    root.setProperty('--sb-border', t.border);
    root.setProperty('--sb-label', t.label);
    root.setProperty('--sb-tx-strong', t.strong);
    root.setProperty('--sb-tx-hover', t.txHover);
    root.setProperty('--sb-tx-faint', t.faint);
  }
  function applySidebarPreset (name) {
    var p = SB_PRESETS[name]; if (!p) return;
    setSidebarPalette(p.bg, p.light);
    czSettings.sidebar = name; czSettings.sidebarCustom = null;
    syncCzUI(); saveCzSettings();
  }
  function applySidebarCustom (hex) {
    setSidebarPalette(hex, isLightColor(hex));
    czSettings.sidebar = 'custom'; czSettings.sidebarCustom = hex;
    syncCzUI(); saveCzSettings();
  }

  /* ── Header palette ── */
  function setHeaderPalette (bg, light, isDefault) {
    if (isDefault) {
      ['--tb-bg', '--tb-tx-1', '--tb-tx-2', '--tb-tx-3', '--tb-border', '--tb-hover'].forEach(function (v) { root.removeProperty(v); });
      return;
    }
    var t = light
      ? { tx1: '#0f1117', tx2: '#5d6070', tx3: '#9496a8', border: 'rgba(15,17,23,.10)', hover: 'rgba(15,17,23,.06)' }
      : { tx1: '#ffffff', tx2: 'rgba(255,255,255,.70)', tx3: 'rgba(255,255,255,.45)', border: 'rgba(255,255,255,.14)', hover: 'rgba(255,255,255,.12)' };
    root.setProperty('--tb-bg', bg);
    root.setProperty('--tb-tx-1', t.tx1);
    root.setProperty('--tb-tx-2', t.tx2);
    root.setProperty('--tb-tx-3', t.tx3);
    root.setProperty('--tb-border', t.border);
    root.setProperty('--tb-hover', t.hover);
  }
  function applyHeaderPreset (name) {
    if (name === 'default') { setHeaderPalette(null, null, true); }
    else if (name === 'dark') { setHeaderPalette('#0c0d12', false, false); }
    else if (name === 'colored') { setHeaderPalette('var(--accent)', false, false); }
    else if (name === 'gradient') { setHeaderPalette('linear-gradient(135deg,var(--accent-grad-1),var(--accent-grad-2))', false, false); }
    czSettings.header = name; czSettings.headerCustom = null;
    syncCzUI(); saveCzSettings();
  }
  function applyHeaderCustom (hex) {
    setHeaderPalette(hex, isLightColor(hex), false);
    czSettings.header = 'custom'; czSettings.headerCustom = hex;
    syncCzUI(); saveCzSettings();
  }

  /* ── Accent ── */
  function applyAccent (hex) {
    root.setProperty('--accent', hex);
    root.setProperty('--accent-s', hexToRgba(hex, .10));
    root.setProperty('--accent-m', hexToRgba(hex, .18));
    root.setProperty('--accent-gl', hexToRgba(hex, .28));
    root.setProperty('--accent-grad-1', shade(hex, 8));
    root.setProperty('--accent-grad-2', shade(hex, -10));
    if (window._sc) {
      window._sc.data.datasets[0].borderColor = hex;
      window._sc.data.datasets[0].pointBackgroundColor = hex;
      window._sc.data.datasets[0].backgroundColor = hexToRgba(hex, .10);
      window._sc.update('none');
    }
    if (window._bc) {
      window._bc.data.datasets[0].backgroundColor = hexToRgba(hex, .72);
      window._bc.update('none');
    }
    czSettings.accent = hex;
    syncCzUI(); saveCzSettings();
  }

  /* ── Layout: position + width ── */
  function applyPosition (pos) {
    $('body').toggleClass('layout-sb-right', pos === 'right');
    czSettings.position = pos;
    syncCzUI(); saveCzSettings();
  }
  function applyWidth (w) {
    $('body').toggleClass('layout-boxed', w === 'boxed');
    czSettings.width = w;
    syncCzUI(); saveCzSettings();
  }

  /* ── Sync active UI states ── */
  function syncCzUI () {
    $('#czSidebarSwatches .cz-swatch').removeClass('active');
    if (czSettings.sidebar !== 'custom') $('#czSidebarSwatches .cz-swatch[data-sb="' + czSettings.sidebar + '"]').addClass('active');
    if (czSettings.sidebarCustom) $('#czSidebarCustom').val(czSettings.sidebarCustom);

    $('#czHeaderSwatches .cz-swatch').removeClass('active');
    if (czSettings.header !== 'custom') $('#czHeaderSwatches .cz-swatch[data-tb="' + czSettings.header + '"]').addClass('active');
    if (czSettings.headerCustom) $('#czHeaderCustom').val(czSettings.headerCustom);

    $('#czAccentSwatches .cz-swatch').removeClass('active');
    $('#czAccentSwatches .cz-swatch[data-accent="' + czSettings.accent + '"]').addClass('active');
    $('#czAccentCustom').val(czSettings.accent);

    $('#czPosRow .cz-pill').removeClass('active');
    $('#czPosRow .cz-pill[data-pos="' + czSettings.position + '"]').addClass('active');

    $('#czWidthRow .cz-pill').removeClass('active');
    $('#czWidthRow .cz-pill[data-width="' + czSettings.width + '"]').addClass('active');
  }

  /* ── Apply everything on load ── */
  function initCustomizer () {
    if (czSettings.sidebar === 'custom' && czSettings.sidebarCustom) setSidebarPalette(czSettings.sidebarCustom, isLightColor(czSettings.sidebarCustom));
    else setSidebarPalette((SB_PRESETS[czSettings.sidebar] || SB_PRESETS.white).bg, (SB_PRESETS[czSettings.sidebar] || SB_PRESETS.white).light);

    if (czSettings.header === 'custom' && czSettings.headerCustom) setHeaderPalette(czSettings.headerCustom, isLightColor(czSettings.headerCustom), false);
    else if (czSettings.header === 'default') setHeaderPalette(null, null, true);
    else if (czSettings.header === 'dark') setHeaderPalette('#0c0d12', false, false);
    else if (czSettings.header === 'colored') setHeaderPalette('var(--accent)', false, false);
    else if (czSettings.header === 'gradient') setHeaderPalette('linear-gradient(135deg,var(--accent-grad-1),var(--accent-grad-2))', false, false);

    root.setProperty('--accent', czSettings.accent);
    root.setProperty('--accent-s', hexToRgba(czSettings.accent, .10));
    root.setProperty('--accent-m', hexToRgba(czSettings.accent, .18));
    root.setProperty('--accent-gl', hexToRgba(czSettings.accent, .28));
    root.setProperty('--accent-grad-1', shade(czSettings.accent, 8));
    root.setProperty('--accent-grad-2', shade(czSettings.accent, -10));

    $('body').toggleClass('layout-sb-right', czSettings.position === 'right');
    $('body').toggleClass('layout-boxed', czSettings.width === 'boxed');

    $('#czModeRow .cz-pill[data-mode="' + savedTheme + '"]').addClass('active');
    syncCzUI();
  }
  initCustomizer();

  /* ── Panel open / close ── */
  $('#customizerBtn').on('click', function (e) {
    e.stopPropagation();
    $('#czPanel').addClass('is-open');
    $('#czOverlay').addClass('is-show');
  });
  $('#czCloseBtn, #czOverlay').on('click', function () {
    $('#czPanel').removeClass('is-open');
    $('#czOverlay').removeClass('is-show');
  });
  $('#czPanel').on('click', function (e) { e.stopPropagation(); });

  /* ── Bindings ── */
  $('#czModeRow .cz-pill').on('click', function () { setMode($(this).data('mode')); });
  $('#czSidebarSwatches .cz-swatch').on('click', function () { applySidebarPreset($(this).data('sb')); });
  $('#czSidebarCustom').on('input', function () { applySidebarCustom(this.value); });
  $('#czHeaderSwatches .cz-swatch').on('click', function () { applyHeaderPreset($(this).data('tb')); });
  $('#czHeaderCustom').on('input', function () { applyHeaderCustom(this.value); });
  $('#czAccentSwatches .cz-swatch').on('click', function () { applyAccent($(this).data('accent')); });
  $('#czAccentCustom').on('input', function () { applyAccent(this.value); });
  $('#czPosRow .cz-pill').on('click', function () { applyPosition($(this).data('pos')); });
  $('#czWidthRow .cz-pill').on('click', function () { applyWidth($(this).data('width')); });

  $('#czResetBtn').on('click', function () {
    localStorage.removeItem('nx-customizer');
    location.reload();
  });

  /* ──────────────────────────────────────────────────
     SIDEBAR — DESKTOP COLLAPSE  (class-based, no inline width)
  ────────────────────────────────────────────────── */
  $('#desktopCollapseBtn').on('click', function () {
    doToggleSidebar();
  });

  function doToggleSidebar () {
    collapsed = !collapsed;

    if (collapsed) {
      $sidebar.addClass('sidebar-collapsed');
      $mainArea.addClass('is-collapsed');
      // Close all inline submenus when collapsing
      $('.nav-sub').stop(true, true).slideUp(220);
      $('.nav-row').removeClass('is-open');
    } else {
      $sidebar.removeClass('sidebar-collapsed');
      $mainArea.removeClass('is-collapsed');
      hideFlyout();
    }
  }
  /* ──────────────────────────────────────────────────
     SIDEBAR — MOBILE
  ────────────────────────────────────────────────── */
  $('#mobileMenuBtn').on('click', function () {
    $sidebar.addClass('mobile-open');
    $('#sbOverlay').addClass('is-show');
  });

  $('#sbOverlay').on('click', function () {
    $sidebar.removeClass('mobile-open');
    $(this).removeClass('is-show');
  });

  /* ──────────────────────────────────────────────────
     NESTED MENU — slideToggle on .nav-row[data-target]
  ────────────────────────────────────────────────── */
  $(document).on('click', '.nav-row[data-target]', function (e) {
    e.stopPropagation();

    // In collapsed mode, don't expand inline submenus
    if (collapsed) return;

    var $row    = $(this);
    var targetId = $row.data('target');
    var $sub    = $('#' + targetId);

    if (!$sub.length) return;

    var isOpen = $row.hasClass('is-open');

    if (isOpen) {
      // Close this branch
      $row.removeClass('is-open');
      $sub.stop(true, true).slideUp(220, function () {
        // Also close any child subs
        $sub.find('.nav-sub').hide();
        $sub.find('.nav-row').removeClass('is-open');
      });
    } else {
      $row.addClass('is-open');
      $sub.stop(true, true).slideDown(220);
    }
  });

  /* ──────────────────────────────────────────────────
     COLLAPSED FLYOUT — hover submenu
  ────────────────────────────────────────────────── */
  // Show flyout on hover over a nav-item-wrap when collapsed
  $(document).on('mouseenter', '#sidebar.sidebar-collapsed .nav-item-wrap', function () {
    if (!collapsed) return;

    var $wrap       = $(this);
    var flyHtml     = $wrap.data('flyout-html') || '';
    var flyTitle    = $wrap.data('flyout-title') || '';

    clearTimeout(flyTimer);
    flyHovered = false;

    // Position flyout beside hovered row
    var rect = this.getBoundingClientRect();

    if (flyHtml) {
      $flyout
        .html(flyHtml)
        .css({ top: rect.top + 'px' })
        .stop(true, true)
        .fadeIn(120);
    } else if (flyTitle) {
      // Leaf item: just show title label
      $flyout
        .html('<div class="fp-title" style="border:none;margin:0;padding:6px 14px;font-size:12px;font-weight:600;color:var(--tx-2);">' + flyTitle + '</div>')
        .css({ top: rect.top + 'px' })
        .stop(true, true)
        .fadeIn(120);
    } else {
      hideFlyout();
    }
  });

  $(document).on('mouseleave', '#sidebar.sidebar-collapsed .nav-item-wrap', function () {
    if (!collapsed) return;
    scheduleFlyoutHide();
  });

  $flyout.on('mouseenter', function () {
    flyHovered = true;
    clearTimeout(flyTimer);
  });

  $flyout.on('mouseleave', function () {
    flyHovered = false;
    scheduleFlyoutHide();
  });

  function scheduleFlyoutHide () {
    flyTimer = setTimeout(function () {
      if (!flyHovered) hideFlyout();
    }, 130);
  }

  function hideFlyout () {
    $flyout.stop(true, true).fadeOut(100);
  }

  /* ──────────────────────────────────────────────────
     TOPBAR DROPDOWNS
  ────────────────────────────────────────────────── */
  function closeAllDd () { $('.tb-dd').removeClass('is-open'); }

  $('#notifBtn').on('click', function (e) {
    e.stopPropagation();
    var $dd = $('#notifDd');
    var was = $dd.hasClass('is-open');
    closeAllDd();
    if (!was) $dd.addClass('is-open');
  });

  $('#profileBtn').on('click', function (e) {
    e.stopPropagation();
    var $dd = $('#profileDd');
    var was = $dd.hasClass('is-open');
    closeAllDd();
    if (!was) $dd.addClass('is-open');
  });

  $(document).on('click', function () { closeAllDd(); hideFlyout(); });
  $('.tb-dd').on('click', function (e) { e.stopPropagation(); });
});
(function () {
  // ── Data source: swap this for your backend / localStorage load ──
  let quickMenuItems = JSON.parse(localStorage.getItem('qmItems') || 'null') || [
    { id: 'projects',  label: 'Projects',  url: '/projects',  icon: 'ri-hourglass-line',        color: 'dark'   },
    { id: 'invoice',   label: 'Invoice',   url: '/invoice',   icon: 'ri-file-list-3-line',       color: 'indigo' },
    { id: 'teams',     label: 'Teams',     url: '/teams',     icon: 'ri-team-line',              color: 'red'    },
    { id: 'chat',      label: 'Chat',      url: '/chat',      icon: 'ri-chat-3-line',            color: 'amber'  },
    { id: 'billing',   label: 'Billing',   url: '/billing',   icon: 'ri-shopping-cart-2-line',   color: 'cyan'   },
    { id: 'payment',   label: 'Payment',   url: '/payment',   icon: 'ri-bank-card-line',         color: 'green'  },
    { id: 'management',label: 'Management',url: '/management',icon: 'ri-shield-star-line',       color: 'violet' },
    { id: 'inbox',     label: 'Inbox',     url: '/inbox',     icon: 'ri-inbox-line',             color: 'pink'   },
  ];

  const trigger    = document.getElementById('qmTrigger');
  const dropdown   = document.getElementById('qmDropdown');
  const grid       = document.getElementById('qmGrid');
  const editToggle = document.getElementById('qmEditToggle');
  const editor     = document.getElementById('qmEditor');
  const editLabel  = document.getElementById('qmEditLabel');
  const editUrl    = document.getElementById('qmEditUrl');

  let editingId = null;   // which item's editor popover is open
  let editMode  = false;  // whether pencil badges are showing

  function persist() {
    localStorage.setItem('qmItems', JSON.stringify(quickMenuItems));
  }

  function renderGrid() {
    grid.innerHTML = quickMenuItems.map(item => `
      <a href="${editMode ? 'javascript:void(0)' : item.url}" class="qm-item" data-id="${item.id}">
        <span class="qm-icon qm-ic-${item.color}"><i class="${item.icon}"></i></span>
        <span class="qm-label">${item.label}</span>
        <button type="button" class="qm-item-edit" data-id="${item.id}" title="Edit">
          <i class="ri-pencil-fill"></i>
        </button>
      </a>
    `).join('');
    grid.classList.toggle('is-editing', editMode);
  }

  // Open / close the whole Quick Menus dropdown
  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('is-open');
  });

  // Toggle edit mode (pencil in header)
  editToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    editMode = !editMode;
    editToggle.classList.toggle('is-active', editMode);
    renderGrid();
    if (!editMode) closeEditor();
  });

  // Click on a per-item pencil badge -> open inline editor
  grid.addEventListener('click', (e) => {
    const pencil = e.target.closest('.qm-item-edit');
    if (!pencil) return;
    e.preventDefault();
    e.stopPropagation();
    openEditor(pencil.dataset.id, pencil);
  });

  function openEditor(id, anchorEl) {
    const item = quickMenuItems.find(i => i.id === id);
    if (!item) return;
    editingId = id;
    editLabel.value = item.label;
    editUrl.value = item.url;

    const rect = anchorEl.getBoundingClientRect();
    editor.style.top  = `${rect.bottom + 6}px`;
    editor.style.left = `${Math.max(12, rect.left - 190)}px`;
    editor.classList.add('is-open');
    editLabel.focus();
  }

  function closeEditor() {
    editor.classList.remove('is-open');
    editingId = null;
  }

  document.getElementById('qmEditCancel').addEventListener('click', (e) => {
    e.stopPropagation();
    closeEditor();
  });

  document.getElementById('qmEditSave').addEventListener('click', (e) => {
    e.stopPropagation();
    const item = quickMenuItems.find(i => i.id === editingId);
    if (item) {
      item.label = editLabel.value.trim() || item.label;
      item.url   = editUrl.value.trim() || item.url;
      persist();
      renderGrid();
    }
    closeEditor();
  });

  // Prevent clicks inside dropdown/editor from bubbling to document close-handler
  dropdown.addEventListener('click', (e) => e.stopPropagation());
  editor.addEventListener('click', (e) => e.stopPropagation());

  // Click outside -> close everything
  document.addEventListener('click', () => {
    dropdown.classList.remove('is-open');
    closeEditor();
  });

  renderGrid();
})();
(function () {
  const STORAGE_KEY = 'app_lang';

  const trigger  = document.getElementById('langTrigger');
  const dropdown = document.getElementById('langDropdown');
  const items    = document.querySelectorAll('.lang-item');

  // Default to English if nothing saved yet
  let currentLang = localStorage.getItem(STORAGE_KEY) || 'en';

  function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem(STORAGE_KEY, lang);

    // Reflect active state in the dropdown
    items.forEach(item => {
      item.classList.toggle('is-active', item.dataset.lang === lang);
    });

    // Set on <html> so CSS/[lang] rules or RTL logic can hook in later
    document.documentElement.setAttribute('lang', lang);

    // Let the rest of the app react (swap text, load translation file, etc.)
    // document.dispatchEvent(new CustomEvent('languagechange', { detail: { lang } }));
  }

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('is-open');
  });

  items.forEach(item => {
    item.addEventListener('click', (e) => {
      e.stopPropagation();
    //   applyLanguage(item.dataset.lang);
      dropdown.classList.remove('is-open');
    });
  });

  dropdown.addEventListener('click', (e) => e.stopPropagation());
  document.addEventListener('click', () => dropdown.classList.remove('is-open'));

  // Initialize on load
//   applyLanguage(currentLang);
})();

// Search
(function () {
  const searchInput = document.getElementById('globalSearchInput');

  document.addEventListener('keydown', (e) => {
    // Alt + K -> focus search, from anywhere on the page
    if (e.altKey && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      searchInput.focus();
      searchInput.select(); // optional: select existing text so typing replaces it
    }

    // Escape -> blur / clear focus while typing in the search box
    if (e.key === 'Escape' && document.activeElement === searchInput) {
      searchInput.blur();
    }
  });
})();

function dropdownParentFor ($el) {
    var $host = $el.closest('.modal, .offcanvas');
    return $host.length ? $host : $(document.body);
  }

//   /* ──────────────────────────────────────────────────
//     OFFCANVAS — open + dynamic width
//   ────────────────────────────────────────────────── */
//   var ofEl       = document.getElementById('sideForm');
//   var ofInstance = bootstrap.Offcanvas.getOrCreateInstance(ofEl);

//   function setOffcanvasWidth (pct) {
//     ofEl.style.setProperty('--of-width', pct + 'vw');
//     $('#ofWidthRow .of-width-btn').removeClass('active');
//     $('#ofWidthRow .of-width-btn[data-set-width="' + pct + '"]').addClass('active');
//   }

//   // Launcher cards — "Open at 50% / 70% / 100%"
//   $('[data-open-width]').on('click', function () {
//     var pct = $(this).data('open-width');
//     setOffcanvasWidth(pct);
//     ofInstance.show();
//   });

  // Width switch buttons inside the offcanvas header
  $('#ofWidthRow .of-width-btn').on('click', function () {
    setOffcanvasWidth($(this).data('set-width'));
  });
  function initSelect2 (scope) {
    if (typeof $.fn.select2 !== 'function') return;
    var $scope = scope ? $(scope) : $(document);

    $scope.find('.js-select2-single:not(.select2-hidden-accessible)').each(function () {
      $(this).select2({
        placeholder    : $(this).data('placeholder') || 'Select…',
        allowClear     : true,
        width          : '100%',
        dropdownParent : dropdownParentFor($(this))
      });
    });
    $scope.find('.js-select2-multi:not(.select2-hidden-accessible)').each(function () {
      $(this).select2({
        placeholder    : $(this).data('placeholder') || 'Select…',
        width          : '100%',
        dropdownParent : dropdownParentFor($(this))
      });
    });
    $scope.find('.js-select2-tags:not(.select2-hidden-accessible)').each(function () {
      $(this).select2({
        tags           : true,
        placeholder    : $(this).data('placeholder') || 'Type…',
        width          : '100%',
        dropdownParent : dropdownParentFor($(this))
      });
    });
}

document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('sidebar');
    const fullLogo = document.getElementById('fullLogo');
    const miniLogo = document.getElementById('miniLogo');
    const sidebarNav = document.getElementById('sidebarNav');
    const navArrowUp = document.getElementById('sbNavArrowUp');
    const navArrowDown = document.getElementById('sbNavArrowDown');

    const STORAGE_KEY = 'sidebarCollapsed';

    function updateLogo() {
        if (sidebar.classList.contains('sidebar-collapsed')) {
            fullLogo.style.display = 'none';
            miniLogo.style.display = 'inline-flex';
            $('.main-area').addClass('is-collapsed');
        } else {
            fullLogo.style.display = 'inline-flex';
            miniLogo.style.display = 'none';
            $('.main-area').removeClass('is-collapsed');
        }
    }

    /* ──────────────────────────────────────────────────
       COLLAPSED SIDEBAR — scroll via up/down arrows
       instead of a scrollbar (arrows only render when
       the icon list actually overflows the viewport).
    ────────────────────────────────────────────────── */
    function refreshNavArrows() {
        if (!sidebarNav || !navArrowUp || !navArrowDown) return;

        var canScroll = sidebar.classList.contains('sidebar-collapsed') &&
            (sidebarNav.scrollHeight - sidebarNav.clientHeight > 4);

        sidebar.classList.toggle('has-nav-arrows', canScroll);

        if (!canScroll) {
            navArrowUp.classList.remove('is-visible');
            navArrowDown.classList.remove('is-visible');
            return;
        }

        navArrowUp.classList.toggle('is-visible', sidebarNav.scrollTop > 4);
        navArrowDown.classList.toggle(
            'is-visible',
            sidebarNav.scrollTop < (sidebarNav.scrollHeight - sidebarNav.clientHeight - 4)
        );
    }

    function scrollNavBy(amount) {
        if (!sidebarNav) return;
        sidebarNav.scrollBy({ top: amount, behavior: 'smooth' });
        setTimeout(refreshNavArrows, 200);
    }

    if (navArrowUp) {
        navArrowUp.addEventListener('click', function () { scrollNavBy(-140); });
    }
    if (navArrowDown) {
        navArrowDown.addEventListener('click', function () { scrollNavBy(140); });
    }
    if (sidebarNav) {
        var navScrollTimer = null;
        sidebarNav.addEventListener('scroll', function () {
            clearTimeout(navScrollTimer);
            navScrollTimer = setTimeout(refreshNavArrows, 60);
        });
    }
    window.addEventListener('resize', refreshNavArrows);

    /* ──────────────────────────────────────────────────
       Bring the current page's menu item into view inside
       the sidebar on load — instantly, no animation — so a
       refresh always shows the active item even if the menu
       had been scrolled to the bottom before reloading.
    ────────────────────────────────────────────────── */
    function revealActiveNavItem() {
        if (!sidebarNav) return;

        var actives = sidebarNav.querySelectorAll('.nav-row.is-active');
        if (!actives.length) return;

        var active = actives[actives.length - 1];
        var navRect = sidebarNav.getBoundingClientRect();
        var activeRect = active.getBoundingClientRect();

        var itemTop = (activeRect.top - navRect.top) + sidebarNav.scrollTop;
        var itemBottom = itemTop + activeRect.height;

        if (itemTop < sidebarNav.scrollTop || itemBottom > sidebarNav.scrollTop + sidebarNav.clientHeight) {
            sidebarNav.scrollTop = Math.max(0, itemTop - (sidebarNav.clientHeight / 2));
        }
    }

    // Restore sidebar state
    if (localStorage.getItem(STORAGE_KEY) === 'true') {
        sidebar.classList.add('sidebar-collapsed');
    } else {
        sidebar.classList.remove('sidebar-collapsed');
    }

    updateLogo();

    // Observe sidebar class changes
    const observer = new MutationObserver(function () {

        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');

        // Save state
        localStorage.setItem(STORAGE_KEY, isCollapsed);

        // Update logo
        updateLogo();

        // Re-check whether the collapsed icon list needs scroll arrows
        refreshNavArrows();

    });

    observer.observe(sidebar, {
        attributes: true,
        attributeFilter: ['class']
    });

    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        $('#sidebar').addClass('sidebar-collapsed');
        $('.nav-sub').hide();
        $('.nav-row').removeClass('is-open');
    } else {
        $('.nav-row.is-active').each(function(){
            $(this).addClass('is-open');
            $('#' + $(this).data('target')).show();
        });
    }

    revealActiveNavItem();
    refreshNavArrows();
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.lang-item').forEach(function (button) {
        button.addEventListener('click', function () {
            fetch('/admin/change-language', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    language: this.dataset.lang
                })
            })
            .then(response => response.json())
            .then(data => {

                if (data.success) {
                    window.location.reload();
                }

            })
            .catch(error => {
                console.error('Language change failed:', error);
            });
        });
    });
});

const input = document.getElementById('globalSearchInput');
const dropdown = document.getElementById('searchDropdown');

/*
|--------------------------------------------------------------------------
| Alt + K
|--------------------------------------------------------------------------
*/

document.addEventListener('keydown', function(e){

    if(e.altKey && e.key.toLowerCase() === 'k'){

        e.preventDefault();

        input.focus();

        input.select();

    }

});


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

input.addEventListener('input', function(){

    const keyword = this.value.trim().toLowerCase();

    if(keyword.length < 3){

        dropdown.innerHTML = "";

        dropdown.style.display = "none";

        return;

    }

    const menus = searchData.menus.filter(item =>
        item.title.toLowerCase().includes(keyword)
    );

    const database = searchData.database.filter(item =>
        item.title.toLowerCase().includes(keyword)
    );

    renderResult(menus,database);

});


function renderResult(menus,database){

    let html = "";

    if(menus.length){

        html += `
            <div class="search-group">

                <div class="search-title">
                    Menu
                </div>
        `;

        menus.forEach(item=>{

            html += `
                <a href="${item.url}" class="search-item">

                    <i class="${item.icon}"></i>

                    <span>${item.title}</span>

                </a>
            `;

        });

        html += "</div>";

    }

    if(database.length){

        html += `
            <div class="search-group">

                <div class="search-title">
                    Database
                </div>
        `;

        database.forEach(item=>{

            html += `
                <a href="${item.url}" class="search-item">

                    <i class="ri-database-2-line"></i>

                    <div>

                        <div>${item.title}</div>

                        <small>${item.type}</small>

                    </div>

                </a>
            `;

        });

        html += "</div>";

    }

    if(!html){

        html = `
            <div class="search-empty">

                No Result Found

            </div>
        `;
    }

    dropdown.innerHTML = html;

    dropdown.style.display = "block";

}


/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

document.addEventListener('click', function(e){

    if(!e.target.closest('.tb-search')){

        dropdown.style.display="none";

    }

});


/*
|--------------------------------------------------------------------------
| Search Call
|--------------------------------------------------------------------------
*/

let timer;

input.addEventListener("input", function () {

    clearTimeout(timer);

    const keyword = this.value.trim();

    if (keyword.length < 3) {
        dropdown.innerHTML = "";
        dropdown.style.display = "none";
        return;
    }

    timer = setTimeout(() => {

        fetch(`/admin/search?q=${encodeURIComponent(keyword)}`)
            .then(res => res.json())
            .then(data => {

                renderResult(data.menus, data.database);

            });

    }, 300);

});
