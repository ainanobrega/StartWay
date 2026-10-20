
<?php

require_once __DIR__ . '/includes/email.php';

$destinatario = 'SEU_EMAIL_PESSOAL@gmail.com';

$urlTeste = 'http://localhost/StartWay/esqueci_senha.php';

if (enviarEmailRecuperacao($destinatario, $urlTeste)) {
    echo 'SUCESSO: o Gmail enviou o e-mail de teste.';
} else {
    echo 'FALHA: o envio não funcionou. Confira o log do Apache.';
}
