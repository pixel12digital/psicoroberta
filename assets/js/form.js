/* Formulário de contato: validação no navegador + envio via enviar.php (sem WordPress/banco) */
(function () {
  var form = document.querySelector('#form-contato form');
  if (!form) return;
  var wrap = form.closest('.wpcf7');
  var out = form.querySelector('.wpcf7-response-output');
  var started = Date.now();
  form.querySelector('[name=ts]').value = started;

  var MSG = {
    required: 'Por favor, preencha este campo.',
    email: 'Por favor, insira um endereço de e‑mail válido.',
    tel: 'Por favor, insira um número de telefone válido.',
    invalid: 'Ocorreram erros de validação. Verifique os campos e tente novamente.',
    fail: 'Falha ao enviar a sua mensagem. Por favor, tente novamente mais tarde.'
  };

  function clearErrors() {
    form.querySelectorAll('.wpcf7-not-valid-tip').forEach(function (n) { n.remove(); });
    form.querySelectorAll('[aria-invalid="true"]').forEach(function (n) { n.setAttribute('aria-invalid', 'false'); });
  }
  function setError(name, msg) {
    var w = form.querySelector('[data-name="' + name + '"]');
    if (!w) return;
    var tip = document.createElement('span');
    tip.className = 'wpcf7-not-valid-tip'; tip.setAttribute('aria-hidden', 'true'); tip.textContent = msg;
    w.appendChild(tip);
    var f = w.querySelector('input,textarea,select'); if (f) f.setAttribute('aria-invalid', 'true');
  }
  function status(cls, text) {
    form.className = 'wpcf7-form ' + cls;
    out.hidden = false; out.textContent = text;
  }
  function validate() {
    var errs = {}, v = function (n) { return (form.elements[n].value || '').trim(); };
    if (!v('nome-completo')) errs['nome-completo'] = MSG.required;
    if (!v('seu-email')) errs['seu-email'] = MSG.required;
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v('seu-email'))) errs['seu-email'] = MSG.email;
    if (!v('seu-telefone')) errs['seu-telefone'] = MSG.required;
    else if (!/^[+]?[0-9()\/ .\-]*$/.test(v('seu-telefone'))) errs['seu-telefone'] = MSG.tel;
    if (!v('sua-mensagem')) errs['sua-mensagem'] = MSG.required;
    if (!form.elements['concordo-privacidade'].checked) errs['concordo-privacidade'] = MSG.required;
    return errs;
  }

  if (/[?&]enviado=1/.test(location.search)) status('sent', 'A sua mensagem foi enviada com sucesso.');
  else if (/[?&]erro=1/.test(location.search)) status('failed', MSG.fail);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var errs = validate(), keys = Object.keys(errs);
    if (keys.length) {
      keys.forEach(function (k) { setError(k, errs[k]); });
      status('invalid', MSG.invalid);
      var first = form.querySelector('[aria-invalid="true"]'); if (first) first.focus();
      return;
    }
    var btn = form.querySelector('.wpcf7-submit');
    btn.disabled = true; form.className = 'wpcf7-form submitting'; out.hidden = true;

    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) { form.reset(); status('sent', d.message); }
        else {
          if (d.errors) Object.keys(d.errors).forEach(function (k) { setError(k, d.errors[k]); });
          status(d.spam ? 'spam' : (d.errors ? 'invalid' : 'failed'), d.message || MSG.fail);
        }
      })
      .catch(function () { status('failed', MSG.fail); })
      .then(function () { btn.disabled = false; form.classList.remove('submitting'); });
  });
})();
