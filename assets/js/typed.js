/* Efeito de digitação do banner (substitui o Typed.js do CDN; mesmos parâmetros do original) */
(function () {
  var el = document.getElementById('typed-text');
  if (!el) return;
  var strings = [
    '<span class="color1">Olá, eu sou a Psicopedagoga Roberta Martins!</span>',
    '<span class="color2">Especialista em desenvolvimento infantil.</span>',
    '<span class="color1">Desbloqueie o potencial do seu filho!</span>',
    '<span class="color2">Estratégias personalizadas e acolhimento familiar.</span>'
  ];
  var typeSpeed = 50, backSpeed = 30, backDelay = 2000;

  var cursor = document.createElement('span');
  cursor.className = 'typed-cursor';
  cursor.setAttribute('aria-hidden', 'true');
  cursor.textContent = '|';
  el.parentNode.insertBefore(cursor, el.nextSibling);

  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) {
    el.innerHTML = strings[0]; return;
  }

  // Converte "<span ..>texto</span>" em tokens: tags entram de uma vez, caracteres um a um
  function tokenize(s) { return s.match(/<[^>]+>|[\s\S]/g) || []; }
  var tokens = strings.map(tokenize);

  function render(list, n) { // n = caracteres visíveis
    var out = '', seen = 0, open = [];
    for (var i = 0; i < list.length; i++) {
      var t = list[i];
      if (t.charAt(0) === '<' && t.length > 1) {
        if (seen >= n && t.charAt(1) !== '/') break;
        out += t;
        if (t.charAt(1) === '/') open.pop(); else open.push(t);
      } else { if (seen >= n) break; out += t; seen++; }
    }
    return out + open.map(function () { return '</span>'; }).join('');
  }
  function visible(list) { return list.filter(function (t) { return !(t.charAt(0) === '<' && t.length > 1); }).length; }

  var idx = 0, pos = 0, typing = true;
  function tick() {
    var list = tokens[idx], total = visible(list);
    if (typing) {
      pos++; el.innerHTML = render(list, pos);
      if (pos >= total) { typing = false; return setTimeout(tick, backDelay); }
      setTimeout(tick, typeSpeed);
    } else {
      pos--; el.innerHTML = render(list, pos);
      if (pos <= 0) { typing = true; idx = (idx + 1) % tokens.length; return setTimeout(tick, 500); }
      setTimeout(tick, backSpeed);
    }
  }
  tick();
})();
