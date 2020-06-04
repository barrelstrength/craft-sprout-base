<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\metadata\schema;

use barrelstrength\sproutbase\app\metadata\base\Schema;

class ContactPointSchema extends Schema
{
    public $contact;

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Contact Point';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'ContactPoint';
    }

    /**
     * @return bool
     */
    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function addProperties()
    {
        $contact = $this->contact;

        if (!$contact) {
            return null;
        }

        $this->addText('contactType', $contact['contactType']);
        $this->addText('telephone', $contact['telephone']);

        return null;
    }
}