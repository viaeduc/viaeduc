<?php
namespace Rpe\PumBundle\Model\Social;

abstract class Log
{
    const TYPE_SEE_GROUP                        = 1;

    const TYPE_JOIN_GROUP_DIRECT                = 2; // Le visiteur rejoint le groupe (public) directement
    const TYPE_JOIN_GROUP_REQUEST               = 3; // Le visiteur rejoint le groupe (privé) après une demande d'invitation acceptée par le groupe
    const TYPE_JOIN_GROUP_INVITATION            = 4; // Le visiteur rejoint le groupe après avoir accepté l'invitation du groupe

    const TYPE_SEE_RESOURCE                     = 5;
    const TYPE_SEE_PUBLICATION                  = 6;
    // const TYPE_SEE_SOMEONE_RESOURCE          = 7;
    // const TYPE_SEE_SOMEONE_PUBLICATION       = 8;

    const TYPE_COMMENT_PUBLICATION              = 9;
    const TYPE_COMMENT_RESOURCE                 = 10;

    const TYPE_RECOMMEND_PUBLICATION            = 11;
    const TYPE_RECOMMEND_RESOURCE               = 12;
    // const TYPE_RECOMMEND_SOMEONE_PUBLICATION = 13;
    // const TYPE_RECOMMEND_SOMEONE_RESOURCE        = 14;

    const TYPE_POST_PUBLICATION                 = 15;
    const TYPE_POST_RESOURCE                    = 16;

    const TYPE_SEE_PROFIL                       = 17;
    const TYPE_SEND_MESSAGE                     = 18;
    const TYPE_BECAME_FRIEND                    = 19;
    const TYPE_BECAME_ADMIN                     = 20;

    const TYPE_BOOKMARK_RESOURCE                = 21;
    const TYPE_BOOKMARK_PUBLICATION             = 22;
    // const TYPE_BOOKMARK_SOMEONE_RESOURCE     = 23;
    // const TYPE_BOOKMARK_SOMEONE_PUBLICATION      = 24;

    const TYPE_BOOKMARK_GROUP                   = 25;
    // const TYPE_BOOKMARK_SOMEONE_GROUP            = 26;

    const TYPE_BOOKMARK_QUESTION                = 27;
    // const TYPE_BOOKMARK_SOMEONE_QUESTION     = 28;

    const TYPE_SHARE_RESOURCE                   = 29;
    const TYPE_SHARE_PUBLICATION                = 30;
}
