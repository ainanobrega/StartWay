
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer-master/src/SMTP.php';

/**
 * Envia um e-mail de recuperação de senha pelo Gmail.
 */
function enviarEmailRecuperacao(string $destinatario, string $url): bool
{
    $arquivoConfig = __DIR__ . '/email_config.php';

    if (!is_file($arquivoConfig)) {
        error_log('StartWay: arquivo includes/email_config.php não encontrado.');
        return false;
    }

    $config = require $arquivoConfig;

    if (
        !is_array($config) ||
        empty($config['email']) ||
        empty($config['senha_app'])
    ) {
        error_log('StartWay: configuração do Gmail ausente ou incompleta.');
        return false;
    }

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2;

    $mail->Debugoutput = function ($mensagem, $nivel) {
        $linha = date('Y-m-d H:i:s')
            . " | SMTP nível {$nivel} | {$mensagem}"
            . PHP_EOL;

        file_put_contents(
            __DIR__ . '/email_diagnostico.log',
            $linha,
            FILE_APPEND
        );
    };

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['email'];
        $mail->Password = str_replace(' ', '', $config['senha_app']);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['email'], 'StartWay');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de senha | StartWay';

        $urlSegura = htmlspecialchars(
            $url,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->Body = '
            <h2>Recuperação de senha - StartWay</h2>
            <p>Recebemos uma solicitação para redefinir sua senha.</p>
            <p>
                <a href="' . $urlSegura . '">
                    Redefinir minha senha
                </a>
            </p>
            <p>Este link é válido por 1 hora.</p>
            <p>Se você não solicitou essa alteração, ignore este e-mail.</p>
        ';

        $mail->AltBody =
            "Para redefinir sua senha no StartWay, acesse:\n"
            . $url . "\n\n"
            . "Este link é válido por 1 hora.";

        $mail->send();

        return true;
    
    } catch (\Throwable $e) {
    $detalhes = date('Y-m-d H:i:s')
        . "\nErro: " . $e->getMessage()
        . "\nPHPMailer: " . $mail->ErrorInfo
        . "\n-------------------------\n";

    file_put_contents(
        __DIR__ . '/email_diagnostico.log',
        $detalhes,
        FILE_APPEND
    );

    return false;
}


}
