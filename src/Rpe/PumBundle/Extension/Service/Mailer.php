<?php
namespace Rpe\PumBundle\Extension\Service;

use Symfony\Component\Templating\EngineInterface;

/**
 * @method void __construct(\Swift_Mailer $mailer, EngineInterface $templating)
 * @method void send($params)
 */
class Mailer
{
    
    /**
     * @var string  $mailer
     * @var string  $templating
     * @var string  $transport
     */
    private $mailer;
    private $templating;
    private $transport;

    /**
     * Construct function
     *
     * @access public
     * @param \Swift_Mailer $mailer
     * @param EngineInterface $templating
     * 
     * @return void
     */
    public function __construct(\Swift_Mailer $mailer, EngineInterface $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->transport = $mailer->getTransport();
        if ($this->transport instanceof \Swift_Transport_SendmailTransport) {
            $this->transport->setCommand('/usr/sbin/sendmail -t');
        }
    }

    /**
     * shortcut to send mail
     * 
     * @access public
     * @param  array $params Parameters for mail
     * 
     * @return void
     */
    public function send($params)
    {
        $message = \Swift_Message::newInstance();

        // Compose mail from params
        $bodyContent = '';
        $type = 'text/html';
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'subject':
                    $message->setSubject($value);
                    break;

                case 'from':
                    $message->setFrom($value);
                    break;

                case 'to':
                    $adrs = (array)$value;
                    foreach ($adrs as $adr) {
                        $message->setTo($adr);
                    }
                    break;

                case 'cc':
                    $adrs = (array)$value;
                    foreach ($adrs as $adr) {
                        $message->setCc($adr);
                    }
                    break;

                case 'body':
                    $bodyContent = $value;
                    break;

                case 'template':
                    if (isset($value['name']) && isset($value['vars'])) {
                        $bodyContent = $this->templating->render($value['name'], $value['vars']);
                    }
                    break;

                case 'type':
                    $type = $value;
                    break;

                case 'attachmentPath':
                    $message->attach(\Swift_Attachment::fromPath($value));
                    break;
            }
        }
        $message->setBody($bodyContent, $type);

        $this->mailer->send($message);
    }
}
