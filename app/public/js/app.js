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

  document.addEventListener('DOMContentLoaded', function () {
    all('[data-toast]').forEach(hideLater);
    all('dialog[data-open-now]').forEach(openDialog);
    if (location.hash === '#instalar') openDialog($('#sheet-install'));
  });

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

  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
  }
})();
