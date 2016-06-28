<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Rpe\PumBundle\Model\Social\Invitation;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Invitation controller
 *
 * @method Response myInvitationsAction(Request $request)
 * @method Response deleteInvitationAction(Request $request, $id)
 * @method Response sendInvitationAction(Request $request, $id)
 * @method array    createUserFromInvitation(Invitation $invitation)
 * @method string   getUserTypeFromEmail($email)
 * @method void     sendEmailFromInvitation(Invitation $invitation)
 *
 */
class InvitationController extends Controller
{
    /**
     * Display current invitations sent from the user.
     *
     * @access public
     * @param  Request $request     A request instance
     * @return Response A Response instance
     *
     * @Route(path="/my-invitations", name="my_invitations", defaults={"_project"="rpe"})
     */
    public function myInvitationsAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user       = $this->getUser();
        $invitation = $this->createObject('invitation');
        $form       = $this->createNamedForm('invitation', 'pum_object', $invitation, array(
            'form_view'   => $this->createFormViewByName('invitation', 'create', $update = false)
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $hasError = false;
            if (true === $user->isInvited()) {
                $form->addError(new FormError($this->get('translator')->trans('invitation.cannotasinvited', array(), 'rpe')));
                $hasError = true;
            }

            $emails = explode(';', $invitation->getEmail());
            $content = $invitation->getContent();

            if (count($emails) > 10) {
                $form->addError(new FormError($this->get('translator')->trans('invitation.mail.plusquedix', array(), 'rpe')));
                $hasError = true;
            }
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $form->addError(new FormError($this->get('translator')->trans('invitation.mail.invalid', array(), 'rpe')));
                    $hasError = true;
                    break;
                }
                $existEmail = $this->getRepository('user')->findOneByEmailPro($email);
                if (null !== $existEmail) {
                    $form->addError(new FormError($this->get('translator')->trans('invitation.mail.existed', array('%email%' => $email), 'rpe')));
                    $hasError = true;
                    break;
                }
            }

            if ($hasError === false) {
                foreach ($emails as $email) {
                    $tempInvitation = $this->createObject('invitation');
                    $tempInvitation
                        ->setInviteby($user)
                        ->setDate(new \DateTime())
                        ->setStatus(Invitation::STATUS_AWAITING)
                        ->setEmail($email)
                        ->setContent($content);

                    $user->addInvitation($tempInvitation);
                    list($invitation, $invited) = $this->createUserFromInvitation($tempInvitation);
                    $this->persist($user, $tempInvitation, $invited)->flush();
                    $this->sendEmailFromInvitation($tempInvitation);
                }
            } else {
                return $this->render('pum://page/user/edit/my_invitations.html.twig', array(
                    'form' => $form->createView()
                ));
            }

            return $this->redirect($this->generateUrl('my_invitations'));
        }

        return $this->render('pum://page/user/edit/my_invitations.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * Action to remove an invitation.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Invitation id
     *
     * @return Response A Response instance
     *
     * @Route(path="/invitation/{id}/delete", name="delete_invitation", defaults={"_project"="rpe"})
     */
    public function deleteInvitationAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $invitation = $this->getRepository('invitation')->find($id);
        $user       = $this->getUser();

        if (null !== $invitation && $invitation->getInviteby() === $user) {
            $user->removeInvitation($invitation);

            $this->remove($invitation->getUser());
            $this->remove($invitation);

            $this->persist($user)->flush();

            return $this->redirect($this->generateUrl('my_invitations'));
        }

        throw new \RuntimeException('Not your invitation');
    }

    /**
     * Action to send the mail linked to the invitation.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Invitation id
     *
     * @uses InvitationController:sendEmailFromInvitation() to send the mail
     *
     * @return Response A Response instance
     *
     * @Route(path="/invitation/{id}/send-mail", name="send_invitation", defaults={"_project"="rpe"})
     */
    public function sendInvitationAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $invitation = $this->getRepository('invitation')->find($id);
        $user       = $this->getUser();

        if (null !== $invitation && $invitation->getInviteby() === $user) {
            $this->sendEmailFromInvitation($invitation);

            return $this->redirect($this->generateUrl('my_invitations'));
        }

        throw new \RuntimeException('Not your invitation');
    }

    /**
     * Create the user based on the invitation.
     *
     * @access private
     * @param  Invitation $invitation     The invitation instance
     *
     * @return array  An array contains invitation instance and user created
     */
    private function createUserFromInvitation(Invitation $invitation)
    {
        $user_type = $this->getUserTypeFromEmail($invitation->getEmail());

        $invited = $this->createObject('user')
            ->setStatus(User::STATUS_TYPE_AWAITING_CONFIRMATION)
            ->setType($user_type)
            ->setEmailPro($invitation->getEmail())
            ->setValidationKey(md5(mt_rand().uniqid().microtime()))
            ->setDate(new \Datetime())
        ;

        $invitation->setUser($invited);

        return array($invitation, $invited);
    }

    /**
     * Get the type of user based on its email address.
     *
     * @access private
     * @param  string $email     Email
     *
     * @return string   Return the user type according to email
     */
    private function getUserTypeFromEmail($email)
    {
        $email = explode('@', $email);
        if (count($email) === 2) {
            $email = '@'.trim(strtolower($email[1]));

            if (null !== $this->getRepository('email_domain')->findOneByDomain($email)) {
                return User::TYPE_COMMON;
            }
        }

        return User::TYPE_INVITED;
    }

    /**
     * Send the invitation mail.
     *
     * @access private
     * @param  Invitation $invitation     Invitation instance
     *
     * @return void
     */
    private function sendEmailFromInvitation(Invitation $invitation)
    {
        $user = $invitation->getUser();

        $this->get('rpe.mailer')->send(array(
            'subject'      => $this->get('translator')->trans('invitation.email_subject', array(), 'rpe'),
            'from'         => $this->getSenderEmail(),
            'to'           => $invitation->getEmail(),
            'template' => array(
                'name'     => 'pum://emails/invitation.html.twig',
                'vars'     => array(
                    'invitation'        => $invitation,
                    'confirmation_link' => $this->generateUrl('invited_register', array('id' => $invitation->getId(), 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
                )
            ),
            'type'         => 'text/html'
        ));
    }
}
