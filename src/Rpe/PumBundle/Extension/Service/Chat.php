<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;
use Monolog\Logger;
use Rpe\PumBundle\Extension\Service\RpeUtilities;

class Chat
{
    /**
     * @var PumContext  $context    Pum context object
     */
    private $context;

    /**
     * @var Logger $logger Monolog Logger
     */
    private $logger;

    /**
     * @var RpeUtilities $rpeUtils RPE Utilities methods
     */
    private $rpeUtils;

    public function __construct(PumContext $context, Logger $logger, RpeUtilities $rpeUtils)
    {
        $this->context = $context;
        $this->logger = $logger;
        $this->rpeUtils = $rpeUtils;
    }

    /**
     * Make request to chat server to update rosters of specified users
     * @access public
     * @param  object $relation Relation object between two users
     * @return boolean true/false
     */
    public function requestRosterUpdate($user, $friend)
    {
        if (false === $this->context->getProjectVars()->getValue('activate_chat', false)) {
            return false;
        }

        $user = $user->getJabberId();
        $friend = $friend->getJabberId();

        $this->logger->info('CHAT: should trigger roster update for [' . $user . ', ' . $friend . ']');

        $jabberIds = json_encode(array($user, $friend));

        $hostDomain = $this->rpeUtils->getParameter('chat.host_domain');
        $hostPort   = $this->rpeUtils->getParameter('chat.host_port');
        $rosterUpdateUrl   = 'http://' . $hostDomain . ($hostPort ? (':' . $hostPort) : '') . '/roster_admin/refresh';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_URL, $rosterUpdateUrl);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jabberIds);

        $response = curl_exec($ch);

        curl_close($ch);

        if (false !== $result = json_decode($response, true)) {
            if ($result['errors'] > 0) {
                $this->logger->error('CHAT: roster update failed');
                $this->logger->error('CHAT: ' . $response);
            } else {
                $this->logger->info('CHAT: roster update success');
                $this->logger->info('CHAT: ' . $response);
            }
        } else {
            $this->logger->error('CHAT: roster update failed');
            $this->logger->error('CHAT: ' . $response);
        }
    }
}