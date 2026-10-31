<?php
/**
 * Envio do formulário de contato (substitui o Contact Form 7).
 * Não usa banco de dados: apenas envia e-mail com mail().
 */

/* ======================= CONFIGURAÇÃO ======================= */
$DESTINATARIO   = 'contato@pixel12digital.com.br'; // <<< TROQUE pelo e-mail que vai RECEBER as mensagens
$NOME_DO_SITE   = 'Psicóloga Roberta Dias – Neuropsicopedagoga';
$REMETENTE      = '';   // vazio = no-reply@SEU-DOMINIO (recomendado: um e-mail existente no seu domínio na Hostmidia)
$USAR_ENVELOPE  = true; // usa -f no mail(); se o envio falhar na hospedagem, tente false
$LIMITE_POR_HORA = 5;   // máx. de envios por IP por hora (anti-spam)
/* ============================================================ */

header('Content-Type: text/html; charset=UTF-8');
$querJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

function responder($ok, $msg, $extra = array()) {
    global $querJson;
    if ($querJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge(array('ok' => $ok, 'message' => $msg), $extra));
    } else {
        header('Location: index.html?' . ($ok ? 'enviado=1' : 'erro=1') . '#contato');
    }
    exit;
}
function limpa($v, $max) {
    $v = is_string($v) ? trim($v) : '';
    $v = str_replace(array("\r", "\n", "\0"), ' ', $v); // evita injeção de cabeçalho
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.html'); exit; }

// Anti-spam: honeypot e tempo mínimo de preenchimento
if (!empty($_POST['website'])) responder(false, 'Sua submissão foi marcada como spam.', array('spam' => true));
if (!empty($_POST['ts']) && ctype_digit($_POST['ts']) && (microtime(true) * 1000 - (float)$_POST['ts']) < 2500) {
    responder(false, 'Sua submissão foi marcada como spam.', array('spam' => true));
}

$nome     = limpa(isset($_POST['nome-completo']) ? $_POST['nome-completo'] : '', 200);
$email    = limpa(isset($_POST['seu-email']) ? $_POST['seu-email'] : '', 200);
$tel      = limpa(isset($_POST['seu-telefone']) ? $_POST['seu-telefone'] : '', 30);
$assunto  = limpa(isset($_POST['assunto']) ? $_POST['assunto'] : '', 200);
$horario  = limpa(isset($_POST['horario-retorno']) ? $_POST['horario-retorno'] : '', 60);
$conheceu = limpa(isset($_POST['opcao-como-conheceu']) ? $_POST['opcao-como-conheceu'] : '', 200);
$msgRaw   = isset($_POST['sua-mensagem']) && is_string($_POST['sua-mensagem']) ? trim($_POST['sua-mensagem']) : '';
$mensagem = function_exists('mb_substr') ? mb_substr($msgRaw, 0, 5000, 'UTF-8') : substr($msgRaw, 0, 5000);
$aceite   = !empty($_POST['concordo-privacidade']);

$req = 'Por favor, preencha este campo.';
$erros = array();
if ($nome === '')     $erros['nome-completo'] = $req;
if ($email === '')    $erros['seu-email'] = $req;
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros['seu-email'] = 'Por favor, insira um endereço de e-mail válido.';
if ($tel === '')      $erros['seu-telefone'] = $req;
elseif (!preg_match('%^[+]?[0-9()/ .-]*$%', $tel)) $erros['seu-telefone'] = 'Por favor, insira um número de telefone válido.';
if ($mensagem === '') $erros['sua-mensagem'] = $req;
if (!$aceite)         $erros['concordo-privacidade'] = $req;
if ($erros) responder(false, 'Ocorreram erros de validação. Verifique os campos e tente novamente.', array('errors' => $erros));

// Limite por IP (arquivo temporário, sem banco; se falhar, não bloqueia)
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0';
$arq = rtrim(sys_get_temp_dir(), '/\\') . '/contato_rl_' . md5($ip);
$agora = time(); $hist = array();
if (is_readable($arq)) {
    foreach (explode(',', (string)@file_get_contents($arq)) as $t) { if ((int)$t > $agora - 3600) $hist[] = (int)$t; }
}
if (count($hist) >= $LIMITE_POR_HORA) responder(false, 'Sua submissão foi marcada como spam.', array('spam' => true));

// Monta o e-mail (mesmo modelo do Contact Form 7 original)
$host = isset($_SERVER['SERVER_NAME']) ? preg_replace('/^www\./', '', $_SERVER['SERVER_NAME']) : 'localhost';
$from = $REMETENTE !== '' ? $REMETENTE : 'no-reply@' . $host;
$assuntoMail = 'Contato pelo site: ' . $assunto;
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$corpo  = "Nome completo: $nome\r\n";
$corpo .= "E-mail:          $email\r\n";
$corpo .= "Telefone:        $tel\r\n";
$corpo .= "Horário:         $horario\r\n";
$corpo .= "Como conheceu:   $conheceu\r\n\r\n";
$corpo .= "Assunto:         $assunto\r\n\r\n";
$corpo .= "Mensagem:\r\n$mensagem\r\n\r\n---\r\n";
$corpo .= "Enviado de: $NOME_DO_SITE — $proto://$host\r\n";

$enc = function ($s) { return '=?UTF-8?B?' . base64_encode($s) . '?='; };
$headers  = 'From: ' . $enc($nome) . ' <' . $from . ">\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$enviado = $USAR_ENVELOPE
    ? @mail($DESTINATARIO, $enc($assuntoMail), $corpo, $headers, '-f' . $from)
    : @mail($DESTINATARIO, $enc($assuntoMail), $corpo, $headers);

if ($enviado) {
    $hist[] = $agora; @file_put_contents($arq, implode(',', $hist), LOCK_EX);
    responder(true, 'A sua mensagem foi enviada com sucesso.');
}
responder(false, 'Falha ao enviar a sua mensagem. Por favor, tente novamente mais tarde.');
