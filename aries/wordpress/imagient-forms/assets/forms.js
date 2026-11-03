/* Imagient Forms — sends a form without leaving the page: checks the fields first (the server checks them again),
   shows each field's error under it, then the form's message, or goes to the page set after sending. */
(function () {
  const init = form => {
    if (form.igfReady === true) return; form.igfReady = true;   // (not form._igf: a form's controls are its properties by name)
    const url = form.dataset.igf, status = form.querySelector('.ig-form__status:not(.is-ok):not(.is-error)') || form.querySelector('.ig-form__status');
    const btn = form.querySelector('.ig-form__button'), label = btn && btn.querySelector('span');
    const msg = k => form.dataset['msg' + k] || '';
    const fieldOf = el => el.closest('.ig-field');
    const setError = (field, text) => {
      if (!field) return;
      const e = field.querySelector('.ig-field__error');
      field.classList.toggle('has-error', !!text);
      field.querySelectorAll('input, select, textarea').forEach(c => text ? c.setAttribute('aria-invalid', 'true') : c.removeAttribute('aria-invalid'));
      if (e) { e.textContent = text || ''; e.hidden = !text; if (text) { e.id = e.id || 'e' + Math.random().toString(36).slice(2); field.querySelector('input, select, textarea')?.setAttribute('aria-errormessage', e.id); } }
    };
    const show = (text, ok) => { if (!status) return; status.hidden = !text; status.textContent = text || ''; status.classList.toggle('is-ok', !!ok); status.classList.toggle('is-error', !ok && !!text); };
    // the browser's own checks, in the form's words
    const check = field => {
      const type = field.dataset.type, ctrls = [...field.querySelectorAll('input, select, textarea')];
      const req = field.classList.contains('is-required');
      let val;
      if (type === 'checkbox' || type === 'radio') val = ctrls.filter(c => c.checked).length ? 'x' : '';
      else if (type === 'consent') val = ctrls[0] && ctrls[0].checked ? 'x' : '';
      else val = (ctrls[0] && ctrls[0].value || '').trim();
      if (req && !val) return msg('Required');
      if (val && type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) return msg('Email');
      if (val && ctrls[0] && ctrls[0].validity && !ctrls[0].validity.valid && type !== 'checkbox' && type !== 'radio') return msg('Invalid');
      return '';
    };
    form.querySelectorAll('.ig-field').forEach(field => {
      const again = () => { if (field.classList.contains('has-error')) setError(field, check(field)); };
      field.addEventListener('input', again); field.addEventListener('change', again);
      field.addEventListener('focusout', e => { if (!field.contains(e.relatedTarget) && field.querySelector('input, select, textarea')?.value) setError(field, check(field)); });
    });
    form.addEventListener('submit', async ev => {
      ev.preventDefault();
      if (form.classList.contains('is-sending')) return;
      show('', true);
      let first = null;
      form.querySelectorAll('.ig-field').forEach(f => { const e = check(f); setError(f, e); if (e && !first) first = f; });
      if (first) { first.querySelector('input, select, textarea')?.focus(); return; }
      form.classList.add('is-sending'); if (btn) btn.disabled = true;
      const was = label ? label.textContent : '';
      if (label && btn.dataset.sending) label.textContent = btn.dataset.sending;
      try {
        const res = await fetch(url, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const r = await res.json().catch(() => ({}));
        if (r.ok) {
          if (r.redirect) { location.href = r.redirect; return; }
          form.reset(); form.querySelectorAll('.ig-field').forEach(f => setError(f, ''));
          show(r.message, true);
          form.classList.add('is-sent');
          if (window.turnstile) try { window.turnstile.reset(); } catch (e) {}
        } else {
          const errs = r.errors || {};
          Object.keys(errs).forEach(n => setError(form.querySelector('.ig-field[data-name="' + n + '"]'), errs[n]));
          show(Object.keys(errs).length ? '' : (r.message || msg('Error')), false);
          const bad = form.querySelector('.ig-field.has-error input, .ig-field.has-error select, .ig-field.has-error textarea'); if (bad) bad.focus();
          if (window.turnstile) try { window.turnstile.reset(); } catch (e) {}
        }
      } catch (e) {
        show(msg('Error'), false);
      }
      form.classList.remove('is-sending'); if (btn) btn.disabled = false; if (label) label.textContent = was;
    });
  };
  const all = () => document.querySelectorAll('form.ig-form[data-igf]').forEach(init);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', all); else all();
})();
