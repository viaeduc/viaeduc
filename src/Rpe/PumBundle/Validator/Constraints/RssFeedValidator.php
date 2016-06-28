<?php

namespace Rpe\PumBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RssFeedValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (false === $feedContent = @file_get_contents($value)) {
            $this->context->addViolation($constraint->unreadableUrl, array('{{ value }}' => $value));
            return;
        }
        
        libxml_use_internal_errors(true);
        if (false === $rss = simplexml_load_string($feedContent)) {
            $this->context->addViolation($constraint->invalidFormat, array('{{ value }}' => $value));
            libxml_clear_errors();
            return;
        }

        if (!($rss->xpath('channel') && $rss->xpath('channel/link') && $rss->xpath('channel/title') && $rss->xpath('channel/description'))) {
            $this->context->addViolation($constraint->invalidRss, array('{{ value }}' => $value));
        }
    }
}
