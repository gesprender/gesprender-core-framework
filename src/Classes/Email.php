<?php
namespace Core\Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
class Email
{
    protected const USER = 'info@gesprender.com';           //SMTP usernames
    protected const PASS = '$EquipoGesprender2022';         //SMTP password
    protected const PORT = 465;                             //Port number for gmail(465) for hotmail(587)
    protected const SMTP_HOST = 'smtp.hostinger.com';       //Set the SMTP server to send through
    protected const SMTP_SECURE = 'ssl';                    //Enable implicit TLS encryption for gmail (ssl), for hotmail(tls)
    protected const USER_FROM = self::USER;
    protected const NAME_FROM = 'Gesprender';
    protected const SMTP_DEBUG = 0;                         //Enable verbose debug output, in dev (2) in production (0)

    private $mail;

    

    public function __construct(){
        $this->mail = new PHPMailer(true);
    }

    /**
     * @param string $subject Enter subject of message
     * @param array $emails Enter array of email addresses 
     */
    public function email(string $subject = '', $messange = '', array $emails = [])
    {
        try {
            foreach ($emails as $key => $email) {

                #Server settings
                $this->mail->SMTPDebug  = self::SMTP_DEBUG;                              
                $this->mail->isSMTP();                                    //Send using SMTP
                $this->mail->Host       = self::SMTP_HOST;                     
                $this->mail->SMTPAuth   = true;                           //Enable SMTP authentication
                $this->mail->Username   = self::USER;
                $this->mail->Password   = self::PASS;
                $this->mail->SMTPSecure = self::SMTP_SECURE;
                $this->mail->Port       =  self::PORT;

                #Recipients
                $this->mail->setFrom(self::USER_FROM, self::NAME_FROM);
                $this->mail->addAddress($email);

                #Content
                $this->mail->isHTML(true);                //Set email format to HTML
                $this->mail->Subject = $subject;
                $this->mail->Body    = $messange;         //You can use HTML here
            
            }

                $this->mail->send();

        } catch (Exception $e) {
            // echo "El mensaje no puede ser enviado. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
    
}