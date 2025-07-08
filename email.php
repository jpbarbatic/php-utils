<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require(BASE_DIR . '/lib/phpmailer/src/PHPMailer.php');
require(BASE_DIR . '/lib/phpmailer/src/SMTP.php');
require(BASE_DIR . '/lib/phpmailer/src/Exception.php');

/**
 * crear_mensaje_plantilla
 *
 * @param  mixed $plantilla
 * @param  mixed $datos
 * @return void
 */
function crear_mensaje_plantilla($plantilla, $datos)
{
    extract($datos);
    ob_start();
    include $plantilla;
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}

/**
 * enviar_mail
 *
 * @param  mixed $destinatario
 * @param  mixed $asunto
 * @param  mixed $mensaje
 * @param  mixed $configuracion
 * @param  mixed $adjuntos
 * @return void
 */
function enviar_mail($destinatario, $asunto = '', $mensaje = '', $configuracion = null, $adjuntos = null)
{
    $mail = new PHPMailer(true);

    if (!$configuracion) {
        $configuracion['smtp_auth'] = SMTP_AUTH;
        $configuracion['smtp_secure'] = SMTP_SECURE;
        $configuracion['smtp_port'] = SMTP_PORT;
        $configuracion['smtp_server'] = SMTP_SERVER;
        $configuracion['smtp_user'] = SMTP_USER;
        $configuracion['smtp_pass'] = SMTP_PASS;
        $configuracion['mail_from'] = MAIL_FROM;
        $configuracion['mail_username'] = MAIL_USERNAME;
    }

    try {
        // Configure PHPMailer
        $mail->isSMTP();
        $mail->CharSet = "utf-8";
        $mail->SMTPAuth = $configuracion['smtp_auth'];
        $mail->SMTPSecure = $configuracion['smtp_secure'];
        $mail->Port = $configuracion['smtp_port'];

        // Configure SMTP Server
        $mail->Host = $configuracion['smtp_server'];
        $mail->Username = $configuracion['smtp_user'];
        $mail->Password = $configuracion['smtp_pass'];

        // Configure Email
        $mail->setFrom($configuracion['mail_from']);
        $mail->addAddress($destinatario);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $mensaje;

        if (isset($adjuntos)) {
            foreach ($adjuntos as $adjunto) {
                $mail->AddAttachment($adjunto['path'], $adjunto['nombre']);
            }
        }

        // send mail
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * queue_mail
 *
 * @param  mixed $mail
 * @return void
 */
function queue_email($email)
{
    $db = db_open();
    if ($db) {
        $id = db_insert($db, 'emails_queue', $email);
        db_close($db);
    }
}

/**
 * send_mails
 *
 * @return void
 */
function send_emails_queue()
{
    $db = db_open();
    $emails = db_query($db, "SELECT * FROM emails_queue");
    print_r($emails);
    foreach ($emails as $email) {
        echo "Enviando correo a: " . $email['mail_to'] . '...';
        $res = enviar_mail($email['mail_to'], 'Saludos', 'Prueba');
        if ($res) {
            echo "OK";
            // TODO: Falta actualizar la cola
        } else {
            echo "KO";
        }
        echo PHP_EOL;
    }
    db_close($db);
}