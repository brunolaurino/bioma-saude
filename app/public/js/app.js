/* Saúde App — melhorias progressivas. Tudo funciona sem JavaScript; aqui só fica mais rápido e agradável. */
(function () {
  'use strict';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function openDialog(d) {
    if (!d) return false;
    if (typeof d.showModal === 'function') { if (!d.open) d.showModal(); } else { d.setAttribute('open', ''); }
    return true;
  }
  function closeDialog(d) {
    if (!d) return;
    if (typeof d.close === 'function') d.close(); else d.removeAttribute('open');
  }

  function toast(text) {
    var wrap = document.createElement('div');
    wrap.className = 'toast-wrap';
    wrap.setAttribute('data-toast', '');
    var box = document.createElement('div');
    box.className = 'bc-toast';
    box.setAttribute('role', 'status');
    box.textContent = text;
    wrap.appendChild(box);
    document.body.appendChild(wrap);
    hideLater(wrap);
  }
  function hideLater(el) { setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 3200); }

  function onPage() {
    all('[data-toast]').forEach(hideLater);
    all('dialog[data-open-now]').forEach(openDialog);
    if (location.hash === '#instalar') openDialog($('#sheet-install'));
  }
  // Com o Turbo, turbo:load roda na primeira carga e a cada troca de tela; sem ele, vale o DOMContentLoaded.
  if (window.Turbo) {
    document.addEventListener('turbo:load', onPage);
    // Diálogos abertos não devem sobreviver à troca de tela.
    document.addEventListener('turbo:before-cache', function () { all('dialog[open]').forEach(closeDialog); });
  } else {
    document.addEventListener('DOMContentLoaded', onPage);
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : e.target.parentElement;
    if (!t) return;

    var opener = t.closest('[data-open]');
    if (opener) {
      if (openDialog(document.getElementById(opener.getAttribute('data-open')))) e.preventDefault();
      return;
    }

    var anchor = t.closest('a[href^="#"]');
    if (anchor && anchor.getAttribute('href').length > 1) {
      var det = document.getElementById(anchor.getAttribute('href').slice(1));
      if (det && det.tagName === 'DETAILS') det.open = true;
    }

    var closer = t.closest('[data-close]');
    if (closer) { e.preventDefault(); closeDialog(closer.closest('dialog')); return; }

    // Clique no fundo escuro fecha o diálogo
    if (t.tagName === 'DIALOG' && t.classList.contains('sheet')) { closeDialog(t); return; }

    var reveal = t.closest('[data-reveal]');
    if (reveal) {
      var input = reveal.closest('.bc-field').querySelector('input');
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      reveal.setAttribute('aria-label', show ? 'Esconder senha' : 'Mostrar senha');
      var ic = reveal.querySelector('i');
      if (ic) { ic.classList.toggle('fa-eye', !show); ic.classList.toggle('fa-eye-slash', show); }
      return;
    }

    var copy = t.closest('[data-copy]');
    if (copy) {
      var text = copy.getAttribute('data-copy');
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () { toast('Copiado'); }, function () { toast('Não foi possível copiar'); });
      } else {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); toast('Copiado'); } catch (err) { toast('Selecione e copie o texto'); }
        document.body.removeChild(ta);
      }
      return;
    }

    var step = t.closest('[data-step]');
    if (step) {
      var target = document.getElementById(step.getAttribute('data-target'));
      if (!target) return;
      var v = parseInt(target.value || '0', 10) + parseInt(step.getAttribute('data-step'), 10);
      var min = target.min !== '' ? parseInt(target.min, 10) : -Infinity;
      var max = target.max !== '' ? parseInt(target.max, 10) : Infinity;
      target.value = Math.max(min, Math.min(max, v));
      target.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });

  // Confirmação antes de enviar formulários sensíveis
  var pending = null;
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-confirm') || form === pending) return;
    var sheet = $('#confirm-sheet');
    if (!sheet || typeof sheet.showModal !== 'function') return; // sem diálogo nativo, segue o envio
    e.preventDefault();
    pending = form;
    pending._submitter = e.submitter || null;
    $('#confirm-title', sheet).textContent = form.getAttribute('data-confirm');
    $('[data-confirm-text]', sheet).textContent = form.getAttribute('data-confirm-text') || '';
    $('[data-confirm-label]', sheet).textContent = form.getAttribute('data-confirm-label') || 'Confirmar';
    openDialog(sheet);
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest || !e.target.closest('[data-confirm-ok]') || !pending) return;
    var form = pending;
    closeDialog($('#confirm-sheet'));
    if (form.requestSubmit) form.requestSubmit(form._submitter || undefined); else form.submit();
    setTimeout(function () { pending = null; }, 0);
  });
  document.addEventListener('close', function (e) {
    if (e.target.id === 'confirm-sheet') setTimeout(function () { pending = null; }, 0);
  }, true);

  // Envio automático (selects e rádios de filtro)
  document.addEventListener('change', function (e) {
    var el = e.target;
    if (el.hasAttribute && el.hasAttribute('data-autosubmit') && el.form) {
      if (el.form.requestSubmit) el.form.requestSubmit(); else el.form.submit();
    }
    if (el.hasAttribute && el.hasAttribute('data-file-label') || (el.type === 'file' && el.closest('.file-btn'))) {
      var name = el.files && el.files[0] ? el.files[0].name : '';
      var box = el.closest('label');
      if (box) {
        box.classList.toggle('has-file', !!name);
        var lbl = box.querySelector('[data-file-name]');
        if (lbl && name) lbl.textContent = name;
      }
    }
  });

  // Evita envio duplo: desabilita o botão enquanto a página carrega
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var btn = e.submitter;
    if (btn && btn.tagName === 'BUTTON') {
      setTimeout(function () { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }, 0);
      setTimeout(function () { btn.disabled = false; btn.removeAttribute('aria-busy'); }, 8000);
    }
  });

  // ---------- Tema claro/escuro ----------
  // A troca é na hora (atributo data-theme no <html>); o servidor só guarda a escolha num cookie.
  var darkMq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  function effectiveTheme() {
    var t = document.documentElement.getAttribute('data-theme');
    return t || (darkMq && darkMq.matches ? 'dark' : 'light');
  }
  function applyTheme(choice) {
    if (choice === 'auto') document.documentElement.removeAttribute('data-theme');
    else document.documentElement.setAttribute('data-theme', choice === 'escuro' ? 'dark' : 'light');
    all('[data-theme-choice] [name="tema"]').forEach(function (b) { b.setAttribute('aria-current', b.value === choice ? 'true' : 'false'); });
    all('[data-theme-toggle] input[name="tema"]').forEach(function (i) { i.value = effectiveTheme() === 'dark' ? 'claro' : 'escuro'; });
  }
  function saveTheme(form, choice) {
    var fd = new FormData(form);
    fd.set('tema', choice);
    return fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } });
  }
  document.addEventListener('submit', function (e) {
    var form = e.target, choice;
    if (form.hasAttribute('data-theme-toggle')) choice = effectiveTheme() === 'dark' ? 'claro' : 'escuro';
    else if (form.hasAttribute('data-theme-choice') && e.submitter) choice = e.submitter.value;
    else return;
    if (!window.fetch || !window.FormData) return; // sem fetch, o formulário segue e a página recarrega
    e.preventDefault();
    e.stopPropagation();
    applyTheme(choice);
    saveTheme(form, choice).catch(function () {});
  }, true);

  // ---------- Notificações push ----------
  var canPush = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext;
  function b64ToBytes(s) {
    var raw = atob((s + '==='.slice((s.length + 3) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function bytesToB64(buf) {
    var s = '', b = new Uint8Array(buf);
    for (var i = 0; i < b.length; i++) s += String.fromCharCode(b[i]);
    return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }
  function sha256Hex(text) {
    return crypto.subtle.digest('SHA-256', new TextEncoder().encode(text)).then(function (h) {
      return Array.prototype.map.call(new Uint8Array(h), function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
    });
  }
  function currentSub() {
    if (!canPush) return Promise.resolve(null);
    return navigator.serviceWorker.ready.then(function (r) { return r.pushManager.getSubscription(); });
  }
  function isIos() { return /iPhone|iPad|iPod/.test(navigator.userAgent); }
  function isStandalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; }

  function setupPush() {
    // Ao sair da conta, este aparelho deixa de receber push.
    all('form[action$="/sair"]').forEach(function (f) {
      currentSub().then(function (s) {
        if (!s) return;
        var inp = f.querySelector('input[name="push_endpoint"]') || document.createElement('input');
        inp.type = 'hidden'; inp.name = 'push_endpoint'; inp.value = s.endpoint; f.appendChild(inp);
      });
    });

    var card = $('[data-push]');
    if (!card) return;
    var iosBlocked = isIos() && !isStandalone();
    if (!canPush && !iosBlocked) return; // navegador sem suporte: o cartão continua escondido
    card.hidden = false;
    var on = $('[data-push-on]', card), off = $('[data-push-off]', card), state = $('[data-push-state]', card);
    if (iosBlocked) { $('[data-push-ios]', card).hidden = false; on.hidden = true; return; }
    if (Notification.permission === 'denied') { $('[data-push-denied]', card).hidden = false; on.hidden = true; return; }

    var known = (card.getAttribute('data-hashes') || '').split(',');
    currentSub().then(function (s) {
      if (!s) return;
      return sha256Hex(s.endpoint).then(function (h) {
        if (known.indexOf(h) === -1) return; // assinado no navegador, mas não na conta: oferece ativar
        on.hidden = true; off.hidden = false;
        off.querySelector('input[name="endpoint"]').value = s.endpoint;
        state.textContent = 'Ativas neste aparelho.';
      });
    });
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-push-on') || form._pushReady) return;
    e.preventDefault();
    var card = form.closest('[data-push]');
    Notification.requestPermission().then(function (perm) {
      if (perm !== 'granted') {
        if (perm === 'denied') { $('[data-push-denied]', card).hidden = false; form.hidden = true; }
        throw new Error('sem permissão');
      }
      return navigator.serviceWorker.ready;
    }).then(function (reg) {
      return reg.pushManager.getSubscription().then(function (s) {
        return s || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(card.getAttribute('data-key')) });
      });
    }).then(function (s) {
      form.querySelector('input[name="endpoint"]').value = s.endpoint;
      form.querySelector('input[name="p256dh"]').value = bytesToB64(s.getKey('p256dh'));
      form.querySelector('input[name="auth"]').value = bytesToB64(s.getKey('auth'));
      form._pushReady = true;
      // Fora do evento atual: o navegador ignora requestSubmit enquanto o primeiro envio ainda está sendo tratado.
      setTimeout(function () { if (form.requestSubmit) form.requestSubmit(); else form.submit(); }, 0);
    }).catch(function (err) {
      if (!err || err.message !== 'sem permissão') toast('Não foi possível ativar as notificações');
    });
  }, true);

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-push-off')) return;
    // Cancela também no navegador; o envio do formulário segue normalmente.
    currentSub().then(function (s) { if (s) s.unsubscribe(); });
  });

  if (window.Turbo) document.addEventListener('turbo:load', setupPush);
  else document.addEventListener('DOMContentLoaded', setupPush);

  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
  }
})();
