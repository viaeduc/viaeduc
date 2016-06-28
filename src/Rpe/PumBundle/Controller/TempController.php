<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Temporation controller for integration test
 *
 */
class TempController extends Controller
{
    /**
     * @Route(path="/legals-mentions", name="legals_mentions", defaults={"_project"="rpe"})
     */
    public function legalsMentionsAction()
    {
        return $this->render('pum://page/legals_mentions.html.twig');
    }

    /**
     * @Route(path="/404", name="404", defaults={"_project"="rpe"})
     */
    public function errorMentionsAction()
    {
        return $this->render('pum://page/404.html.twig');
    }


    /**
     * @Route(path="/favorite-search", name="favorite_search", defaults={"_project"="rpe"})
     */
    public function favoriteSearchAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/favorite_search.html.twig');
    }


    /**
     * @Route(path="/friend-page", name="friend_page", defaults={"_project"="rpe"})
     */
    public function friendPageAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/friend_page.html.twig');
    }


    /**
     * @Route(path="/create-personal-page", name="create_personal_page", defaults={"_project"="rpe"})
     */
    public function createPersonalPageAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/create_personal_page.html.twig');
    }

    //

    /**
     * @Route(path="/app-page", name="app_page", defaults={"_project"="rpe"})
     */
    public function appPageAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/app_page.html.twig');
    }

    /**
     * @Route(path="/app-store", name="app_store", defaults={"_project"="rpe"})
     */
    public function appStoreAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/app_store.html.twig');
    }

    /**
     * @Route(path="/my-apps", name="my_apps", defaults={"_project"="rpe"})
     */
    public function myAppsAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/my_apps.html.twig');
    }

    /**
     * @Route(path="/my-publications", name="my_publications", defaults={"_project"="rpe"})
     */
    public function myPublicationsAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/publications/my_publications.html.twig');
    }

    /**
     * @Route(path="/my-relationships", name="my_relationships", defaults={"_project"="rpe"})
     */
    public function myRelationshipsAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/my_relationships.html.twig');
    }

    /**
     * @Route(path="/relationships_waiting_list", name="relationships-waiting-list", defaults={"_project"="rpe"})
     */
    public function relationshipsWaitingListAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/relationships_waiting_list.html.twig');
    }

    /**
     * @Route(path="/messagerie", name="messagerie", defaults={"_project"="rpe"})
     */
    public function messengerAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/chat/inbox.html.twig');
    }

    /**
     * @Route(path="/submenu_profil", name="submenu-profil", defaults={"_project"="rpe"})
     */
    public function submenuProfilAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://includes/common/header/menu/middle/profile-middle.html.twig');
    }

    /**
     * @Route(path="/edit_ressource_new", name="edit-ressource-new", defaults={"_project"="rpe"})
     */
    public function editRessourceNewAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/publications/form-publish.html.twig');
    }

     /**
     * @Route(path="/new_password", name="new-password", defaults={"_project"="rpe"})
     */
    public function passwordResetAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/user/edit/password_reset.html.twig');
    }

    /**
    * @Route(path="/temp_search_autocomplete", name="temp-search-autocomplete", defaults={"_project"="rpe"})
    */
    public function autocompleteAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/temp_search_autocomplete.html.twig');
    }

    /**
    * @Route(path="/temp_editor", name="temp-editor", defaults={"_project"="rpe"})
    */
    public function tempEditorAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/editor.html.twig');
    }

    /**
     * @Route(path="/group-suggestions", name="group_suggestions", defaults={"_project"="rpe"})
     */
    public function groupSuggestionsCreateAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/group/group_suggestions.html.twig');
    }

    /**
     * @Route(path="/chart", name="chart", defaults={"_project"="rpe"})
     */
    public function chartAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/chart.html.twig');
    }

    /**
     * @Route(path="/templist", name="templist", defaults={"_project"="rpe"})
     */
    public function tempListAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/temp_list.html.twig');
    }

    /**
     * @Route(path="/contact", name="contact", defaults={"_project"="rpe"})
     */
    public function contactActionList()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/user/contact.html.twig');
    }

    /**
     * @Route(path="/charte", name="charte", defaults={"_project"="rpe"})
     */
    public function charteAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $this->render('pum://page/charte_no_login.html.twig');
        }

        return $this->render('pum://page/charte.html.twig');
    }
}
