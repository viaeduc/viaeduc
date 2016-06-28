<?php

namespace Rpe\PumBundle\FormView;

use Pum\Core\Definition\View\FormView;
use Pum\Core\Definition\View\FormViewField;
use Pum\Bundle\CoreBundle\PumContext;

class FormViewFactory
{
    /**
     * @var PumContext
     */
    private $context;

    public function __construct(PumContext $context)
    {
        $this->context = $context;
    }

    # create dynamic formvqw
    public function create($objectName, $fields)
    {
        $objectDefinition = $this->context->getProject()->getObject($objectName);

        $sequence = 1;
        $formView = new FormView($objectDefinition);
        foreach ($fields as $fieldOptions) {
            $fieldOptions = array_merge(array(
                'label'       => null,
                'placeholder' => null,
                'help'        => null,
                'options'     => array(),
                ), $fieldOptions);

            $formViewField = FormViewField::create($fieldOptions['label'], $objectDefinition->getField($fieldOptions['field']), 'default', $sequence++, $fieldOptions['placeholder'], $fieldOptions['help'])
                ->setOptions($fieldOptions['options'])
            ;

            $formView->addField($formViewField);
        }

        return $formView;
    }

    # create dynamic formview
    public function getFormViewByName($objectName, $name, $update = false)
    {
        $objectDefinition = $this->context->getProject()->getObject($objectName);
        if (false === $update && $objectDefinition->hasFormView($name)) {
            return $objectDefinition->getFormView($name);
        }

        $fields   = $this->getFormViewData($objectName, $name);
        $formView = $this->create($objectName, $fields);
        $formView->setName($name);

        /*if ($objectDefinition->hasFormView($name)) {
            $objectDefinition->removeFormView($objectDefinition->getFormView($name));
        }

        $objectDefinition->addFormView($formView);
        $this->context->getContainer()->get('pum')->saveBeam($objectDefinition->getBeam());*/

        return $formView;
    }

    private function getFormViewData($objectName, $name)
    {
        $method = 'getFormView'.ucfirst(strtolower($objectName)).'Data';
        if (!method_exists($this, $method)) {
            throw new \RuntimeException(sprintf('No formview data for objectName "%s".', $objectName));
        }

        return $this->$method($name);
    }

    private function getFormViewUserData($name)
    {
        switch ($name) {
            case 'avatar':
                return array(
                    array("field" => "avatar", "label" => "Image de profil", "placeholder" => "", "help" => ""),
                );

            case 'cover':
                return array(
                    array("field" => "original_cover", "label" => "Image de couverture", "placeholder" => "", "help" => ""),
                );

            case 'register':
                return array(
                    array("field" => "sex", "label" => "Civilité*"),
                    array("field" => "lastname", "label" => "Nom*"),
                    array("field" => "firstname", "label" => "Prénom*"),
                    array("field" => "occupation", "label" => "Activité professionnelle*", "options" => array("form_type" => "static")),
                    array("field" => "academy", "label" => "Académie*", "placeholder" => "", "help" => "", "options" => array("form_type" => "static", "force_type" => "rpe_academy")),
                    array("field" => "email_pro", "label" => "Email académique*"),
                    array("field" => "password", "label" => "Mot de passe*"),
                );

            case 'register_more_infos':
                return array(
                    array("field" => "institution_name", "label" => "Votre établissement", "placeholder" => "Ex: Collège Charles Delauney", "help" => "Inscrivez ici le nom de votre établissement."),
                    array("field" => "instructed_disciplines", "label" => "Disciplines enseignées", "placeholder" => "", "help" => "Ecrivez la ou les disciplines que vous enseignez.", "options" => array("form_type" => "ajax")),
                    array("field" => "teaching_levels", "label" => "Niveau d'enseignement", "options" => array("form_type" => "ajax")),
                    array("field" => "profile", "label" => "Votre profil en quelques mots", "placeholder" => "", "help" => "Votre présentation permettra aux autres utilisateurs de mieux comprendre votre profil et entrer en contact avec vous plus facilement."),
                    array("field" => "interests", "label" => "Centres d'intérêts", "placeholder" => "", "help" => "Votre présentation permettra aux autres utilisateurs de mieux comprendre votre profil et entrer en contact avec vous plus facilement.", "options" => array("form_type" => "ajax")),
                    array("field" => "birthdate", "label" => "Date de naissance", "placeholder" => "", "help" => ""),
                    array("field" => "phone", "label" => "Numéro de téléphone", "placeholder" => "Ex: 0663399555", "help" => "Entrez votre numero de téléphone si vous souhaitez être joignable par téléphone par les autres membres du réseau."),
                    array("field" => "website", "label" => "Site web personnel", "placeholder" => "Ex: http://marcdupont.blog.fr", "help" => "Inscrivez ici l'adresse de votre site internet personnel ou de votre blog."),
                    array("field" => "avatar", "label" => "Image de profil", "placeholder" => "", "help" => ""),
                    array("field" => "email_private", "label" => "Adresse mail personnelle", "placeholder" => "", "help" => ""),
                );
            case 'personal_information_addition':
                return array(
                    array("field" => "avatar", "label" => "Image de profil", "placeholder" => "", "help" => ""),
                    array("field" => "occupation", "label" => "Activité professionnelle", "options" => array("form_type" => "static")),
                    array("field" => "academy", "label" => "Académie", "placeholder" => "", "help" => "", "options" => array("form_type" => "static")),
                    array("field" => "city", "label" => "Ville", "placeholder" => ""),
                    array("field" => "institution_name", "label" => "Votre établissement", "placeholder" => "Ex: Collège Charles Delauney", "help" => "Inscrivez ici le nom de votre établissement."),
                    array("field" => "instructed_disciplines", "label" => "Disciplines enseignées", "placeholder" => "", "help" => "Ecrivez la ou les disciplines que vous enseignez.", "options" => array("form_type" => "ajax")),
                    array("field" => "teaching_levels", "label" => "Niveau d'enseignement", "options" => array("form_type" => "ajax")),
                    array("field" => "profile", "label" => "Votre profil en quelques mots", "placeholder" => "", "help" => "Votre présentation permettra aux autres utilisateurs de mieux comprendre votre profil et entrer en contact avec vous plus facilement."),
                    array("field" => "interests", "label" => "Centres d'intérêts", "placeholder" => "", "help" => "Votre présentation permettra aux autres utilisateurs de mieux comprendre votre profil et entrer en contact avec vous plus facilement.", "options" => array("form_type" => "ajax")),
                    array("field" => "birthdate", "label" => "Date de naissance", "placeholder" => "", "help" => ""),
                    array("field" => "phone", "label" => "Numéro de téléphone", "placeholder" => "Ex: 0663399555", "help" => "Entrez votre numero de téléphone si vous souhaitez être joignable par téléphone par les autres membres du réseau."),
                    array("field" => "website", "label" => "Site web personnel", "placeholder" => "Ex: http://marcdupont.blog.fr", "help" => "Inscrivez ici l'adresse de votre site internet personnel ou de votre blog."),
                );
            case 'personal_information_basic':
                return array(
                    array("field" => "sex", "label" => "Civilité*"),
                    array("field" => "firstname", "label" => "Prénom", "placeholder" => "", "help" => ""),
                    array("field" => "lastname", "label" => "Nom", "placeholder" => "", "help" => ""),
                    array("field" => "email_private", "label" => "Adresse mail personnelle", "placeholder" => "", "help" => ""),
                    array("field" => "email_pro", "label" => "Adresse mail professionnelle", "placeholder" => "", "help" => "")
                );
            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "user".', $name));
        }
    }

    private function getFormViewGroupData($name)
    {
        switch ($name) {
            case 'avatar':
                return array(
                    array("field" => "picture", "label" => "Image du groupe", "placeholder" => "", "help" => ""),
                );

            case 'cover':
                return array(
                    array("field" => "original_cover", "label" => "Image de couverture", "placeholder" => "", "help" => ""),
                );

            case 'create':
                return array(
                    array("field" => "name", "label" => "Nom du groupe", "placeholder" => "Ex: Groupe 1"),
                    array("field" => "description", "label" => "Description", "placeholder" => "Ex: groupe de réflexion sur le programme de mathématiques de 5e"),
                    array("field" => "picture", "label" => "Ajouter une image"),
                    array("field" => "instructed_disciplines", "label" => "Discipline", "options" => array("form_type" => "ajax")),
                    array("field" => "teaching_levels", "label" => "Niveau d'enseignement", "options" => array("form_type" => "ajax")),
                    array("field" => "interests", "label" => "Centres d'intérêts", "options" => array("form_type" => "ajax")),
                    array("field" => "accesstype", "label" => "Type d'accès au groupe", "options" => array("expanded" => true)),
                    //array("field" => "authorised_to_invite", "label" => "Autoriser les membres à inviter"),
                    //array("field" => "subgroup_level", "label" => "Création de sous-groupes", "options" => array()),
                );

            case 'edit':
                return array(
                    array("field" => "name", "label" => "Nom du groupe", "placeholder" => "Ex: Groupe 1"),
                    array("field" => "description", "label" => "Description", "placeholder" => "Ex: groupe de réflexion sur le programme de mathématiques de 5e"),
                    array("field" => "picture", "label" => "Ajouter une image"),
                    array("field" => "instructed_disciplines", "label" => "Discipline", "options" => array("form_type" => "ajax")),
                    array("field" => "teaching_levels", "label" => "Niveau d'enseignement", "options" => array("form_type" => "ajax")),
                    array("field" => "interests", "label" => "Centres d'intérêts", "options" => array("form_type" => "ajax")),
                    array("field" => "accesstype", "label" => "Type d'accès au groupe", "options" => array("expanded" => true)),
                    //array("field" => "authorised_to_invite", "label" => "Autoriser les membres à inviter"),
                    array("field" => "subgroup_level", "label" => "Création de sous-groupes", "options" => array()),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "group".', $name));
        }
    }

    private function getFormViewPostData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                        array("field" => "content", "label" => "Ressource", "placeholder" => "Ecrivez votre message..."),
                        array("field" => "file", "label" => "Ajouter un fichier"),
                    );

            case 'create':
                return array(
                    array("field" => "name", "label" => "Ressource", "placeholder" => "Titre de la ressource"),
                    array("field" => "content", "label" => "Contenu :", "placeholder" => "Ecrivez ici le corps de texte de votre ressource"),
                    array("field" => "file", "label" => "Illustration de la ressource :", "help" => "(W / H)"),
                    array("field" => "description", "label" => "Décrivez votre ressource en quelques mots :"),
                    array("field" => "disciplines", "label" => "Disciplines abordées :", "options" => array("form_type" => "ajax")),
                    array("field" => "teaching_levels", "label" => "Niveaux d'enseignement :", "options" => array("form_type" => "ajax")),
                    // array("field" => "medias", "label" => "Medias :"),
                    // array("field" => "sources", "label" => "Sources :"),
                    // array("field" => "visibility", "label" => "Visible par :"),
                    array("field" => "co_authors", "label" => "Co-auteurs de la ressource :", "options" => array("force_type" => "rpe_my_friends", "form_type" => "ajax")),
                    array("field" => "published_group", "label" => "Groupe :", "options" => array("form_type" => "static", "force_type" => "rpe_my_group")),

                    array("field" => "keywords", "label" => "Mots clés :"),
                    //array("field" => "coverage", "label" => "Couverture :"),
                    array( "field" => "document_type", "label" => "Type de document :", "options" => array("form_type" => "static")),
                    array("field" => "copyright", "label" => "Droit(s) d'auteur :"),
                    //array("field" => "linked_posts", "label" => "Posts liés :"),
                    array("field" => "language", "label" => "Langue :", "options" => array("form_type" => "static")),
                    array("field" => "license", "label" => "Licence :", "options" => array("form_type" => "static"))
                );

            case 'share_post':
                return array(
                    array("field" => "content", "label" => "Commentaire :", "placeholder" => "Vous pouvez écrire ici un commentaire sur la publication que vous souhaitez partager")
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "post".', $name));
        }
    }

    private function getFormViewExternal_noticeData($name)
    {
        switch ($name) {
            case 'external_share_notice':
                return array(
                    array("field" => "description", "label" => "Commentaire :", "placeholder" => "Vous pouvez écrire ici un commentaire sur la notice que vous souhaitez partager")
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "notice".', $name));
        }
    }

    private function getFormViewCommentData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                    array("field" => "content", "label" => "", "placeholder" => "Ajouter un commentaire..."),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "comment".', $name));
        }
    }

    private function getFormViewExperienceData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "company", "label" => "Établissement : "),
                    array("field" => "title", "label" => "Intitulé de poste : "),
                    array("field" => "startdate", "label" => "Période : "),
                    array("field" => "enddate", "label" => "à "),
                    array("field" => "description", "label" => "Description : "),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "experience".', $name));
        }
    }

    private function getFormViewFormationData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "degree", "label" => "Diplôme : "),
                    array("field" => "place", "label" => " École, Université : "),
                    array("field" => "startdate", "label" => "Période : "),
                    array("field" => "enddate", "label" => "à "),
                    // array("field" => "name", "label" => "Intitulé : "),
                    array("field" => "description", "label" => "Description : "),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "formation".', $name));
        }
    }

    private function getFormViewQuestionData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "name", "label" => "Votre question :", "placeholder" => "Posez votre question"),
                    array("field" => "description", "label" => "Description : ", "placeholder" => "Explicitez et précisez votre question"),
                    array("field" => "instructed_disciplines", "label" => "Disciplines enseignées : ", "options" => array("form_type" => "ajax")),
                    array("field" => "keywords", "label" => "Mots clés : ", "placeholder" => "Exemple : mot1, mot2, mot3"),
                    array("field" => "published_group", "label" => "Publier dans : ", "placeholder" => "",  "options" => array("form_type" => "static", "force_type" => "rpe_my_question_group")),
                    array("field" => "accesstype", "label" => "Type d'accès à la question : ", "options" => array("expanded" => true))
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "question".', $name));
        }
    }

    private function getFormViewAnswerData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                    array("field" => "content", "label" => "Contenu : ", "placeholder" => "Ajouter une réponse..."),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "answer".', $name));
        }
    }

    private function getFormViewFolderData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                    array("field" => "name", "label" => "Nom : ", "placeholder" => "Nom du dossier"),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "answer".', $name));
        }
    }
    
    private function getFormViewSocial_group_themeData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                array("field" => "name", "label" => "Nom : ", "placeholder" => "Nom du theme"),
                );
    
            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "answer".', $name));
        }
    }

    private function getFormViewMediaData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                    array("field" => "name", "label" => "Nom : ", "placeholder" => "Nom du document"),
                    array("field" => "description", "label" => "Description : ", "placeholder" => "Description"),
                    array("field" => "media", "label" => "")
                );
            case 'upload':
                return array(
                    array("field" => "media", "label" => "")
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "answer".', $name));
        }
    }

    private function getFormViewDiscussionData($name)
    {
        switch ($name) {
            case 'create':
                return array();

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "discussion".', $name));
        }
    }

    private function getFormViewMessageData($name)
    {
        switch ($name) {
            case 'simple':
                return array(
                    array("field" => "content", "label" => "Message : ", "placeholder" => "Répondre"),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "message".', $name));
        }
    }

    private function getFormViewInvitationData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "email", "label" => "Email : ", "placeholder" => ""),
                    array("field" => "content", "label" => "Votre message : ", "placeholder" => ""),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "invitation".', $name));
        }
    }

    private function getFormViewReportData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "content", "label" => "Détails : ", "placeholder" => ""),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "report".', $name));
        }
    }

    private function getFormViewEventData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "title", "label" => "Nom", "placeholder" => ""),
                    array("field" => "start_date", "label" => "Début", "placeholder" => "", "options" => array("input" => "datetime", "widget" => "single_text", "format" => "dd/mm/yyyy hh:ii:ss")),
                    array("field" => "end_date", "label" => "Fin", "placeholder" => ""),
                    array("field" => "place_name", "label" => "Lieu", "placeholder" => ""),
                    array("field" => "place_address", "label" => "Adresse", "placeholder" => ""),
                    array("field" => "description", "label" => "Description", "placeholder" => ""),
                    array("field" => "owner_group", "label" => "Publier dans", "placeholder" => "",  "options" => array("form_type" => "static", "force_type" => "rpe_my_event_group")),
                    array("field" => "privacy", "label" => "Invitation", "placeholder" => ""),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "event".', $name));
        }
    }

    private function getFormViewEditorData($name)
    {
        switch ($name) {
        	case 'picture':
                return array(
                    array("field" => "picture", "label" => "Image de editeur", "placeholder" => "", "help" => ""),
                );

            case 'cover':
                return array(
                    array("field" => "cover", "label" => "Image de couverture", "placeholder" => "", "help" => ""),
                );

        	case 'create':
        	    return array(
            	    array("field" => "name", "label" => "Nom du partenaire", "placeholder" => "Ex: Partenaire 1"),
            	    array("field" => "description", "label" => "Description", "placeholder" => "Ex: partenaire de réflexion sur le programme de mathématiques de 5e"),
            	    array("field" => "picture", "label" => "Ajouter une image"),
            	    array("field" => "email", "label" => "Email"),
            	    array("field" => "website", "label" => "Website", "placeholder" => "http://"),
            	    array("field" => "active", "label" => "Cette page doit être visible des utilisateurs de Viaéduc")
        	    );

        	case 'edit':
        	    return array(
            	    array("field" => "name", "label" => "Nom du groupe", "placeholder" => "Ex: Partenaire 1"),
            	    array("field" => "description", "label" => "Description", "placeholder" => "Ex: groupe de réflexion sur le programme de mathématiques de 5e"),
            	    array("field" => "picture", "label" => "Ajouter une image"),
            	    array("field" => "email", "label" => "Email"),
            	    array("field" => "website", "label" => "Website", "placeholder" => "http://"),
            	    array("field" => "active", "label" => "Cette page doit être visible des utilisateurs de Viaéduc")
        	    );

        	default:
        	    throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "editor".', $name));
        }
    }

    private function getFormViewProductData($name)
    {
        switch ($name) {
        	case 'picture':
        	    return array(
        	       array("field" => "picture", "label" => "Image de produit", "placeholder" => "", "help" => ""),
        	    );

        	case 'create':
        	    return array(
        	        array("field" => "title", "label" => "Titre"),
        	        array("field" => "subtitle", "label" => "Sous-titre"),
        	        array("field" => "type", "label" => "Type"),
        	        array("field" => "linkmore", "label" => "En savoir plus", "placeholder" => "http://"),
        	        array("field" => "picture", "label" => "Ajouter une image"),
            	    array("field" => "content", "label" => "Contenu", "placeholder" => "Ex: Product content")
        	    );

        	case 'edit':
        	    return array(
            	    array("field" => "title", "label" => "Titre"),
        	        array("field" => "subtitle", "label" => "Sous-titre"),
        	        array("field" => "type", "label" => "Type"),
        	        array("field" => "linkmore", "label" => "En savoir plus", "placeholder" => "http://"),
        	        array("field" => "picture", "label" => "Ajouter une image"),
            	    array("field" => "content", "label" => "Contenu", "placeholder" => "Ex: Product content")
        	    );

        	default:
        	    throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "product".', $name));
        }
    }

    private function getFormViewBlogData($name)
    {
        switch ($name) {
            case 'cover':
                return array(
                    array("field" => "cover", "label" => "Image de couverture", "placeholder" => "", "help" => ""),
                );

            case 'create':
                return array(
                    array("field" => "name", "label" => "Nom du blog", "placeholder" => ""),
                    array("field" => "description", "label" => "Description", "placeholder" => ""),
                    array("field" => "accesstype", "label" => "Type d'accès au blog", "options" => array("expanded" => true)),
                );

            case 'edit':
                return array(
                    array("field" => "name", "label" => "Nom du blog", "placeholder" => ""),
                    array("field" => "description", "label" => "Description", "placeholder" => ""),
                    array("field" => "accesstype", "label" => "Type d'accès au blog", "options" => array("expanded" => true)),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "blog".', $name));
        }
    }

    private function getFormViewSurveyData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "question", "label" => "Question"),
                    array("field" => "multiple", "label" => "Type de sondage"),
                    array("field" => "start_date", "label" => "Début"),
                    array("field" => "end_date", "label" => "Fin"),
                );

            case 'answer':
                return array();

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "blog".', $name));
        }
    }

    private function getFormViewContactData($name)
    {
        switch ($name) {
            case 'create':
                return array(
                    array("field" => "name", "label" => "Votre nom"),
                    array("field" => "firstname", "label" => "Votre prénom"),
                    array("field" => "mailfrom", "label" => "Votre adresse mail"),
                    array("field" => "subject", "label" => "L'objet de votre message"),
                    array("field" => "content", "label" => "Votre message"),
                );

            default:
                throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "blog".', $name));
        }
    }

    private function getFormViewSocial_oauth_keyData($name)
    {
        switch ($name) {
        	case 'create':
        	    return array(
            	    array("field" => "app_name", "label" => "Nom de votre application"),
        	    );

        	default:
        	    throw new \RuntimeException(sprintf('No formview data named "%s" in objectname "oauth_key".', $name));
        }
    }
}
