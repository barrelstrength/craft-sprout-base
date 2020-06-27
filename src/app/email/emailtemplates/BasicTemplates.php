<?php

namespace barrelstrength\sproutbase\app\email\emailtemplates;

use barrelstrength\sproutbase\app\email\base\EmailTemplates;
use Craft;

class BasicTemplates extends EmailTemplates
{
    public function getName(): string
    {
        return Craft::t('sprout', 'Default Message');
    }

    public function getTemplateRoot(): string
    {
        return Craft::getAlias('@sproutbase/templates/notifications');
    }

    public function getPath(): string
    {
        return '_components/emailtemplates/basic';
    }
}



