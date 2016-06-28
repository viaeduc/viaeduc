<?php

namespace Rpe\PumBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RssFeed extends Constraint
{
    public $unreadableUrl = 'Le contenu de l\'url "{{ value }}" est inaccessible';
    public $invalidFormat = 'L\'url "{{ value }}" est un flux mal formaté';
    public $invalidRss    = 'L\'url "{{ value }}" n\'est pas un flux rss valide';
}
