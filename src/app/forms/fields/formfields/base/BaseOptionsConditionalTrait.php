<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\forms\fields\formfields\base;

use barrelstrength\sproutbase\app\forms\base\ConditionInterface;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsCondition;
use barrelstrength\sproutbase\app\forms\rules\conditions\IsNotCondition;

trait BaseOptionsConditionalTrait
{
    /**
     * @inheritdoc
     */
    public function getConditionValueInputHtml(ConditionInterface $condition, $fieldName, $fieldValue): string
    {
        $html = '<input class="text fullwidth" type="text" name="'.$fieldName.'" value="'.$fieldValue.'">';

        $selectConditionClasses = [
            IsCondition::class,
            IsNotCondition::class,
        ];

        foreach ($selectConditionClasses as $selectCondition) {
            if ($condition instanceof $selectCondition) {
                $html = '<div class="select"><select name="'.$fieldName.'">';
                $firstRow = 'selected';
                foreach ($this->options as $option) {
                    $rowValue = $option['value'];
                    $label = $option['label'];
                    $isSelected = $rowValue == $fieldValue ? 'selected' : '';
                    $html .= '<option '.$firstRow.' value="'.$rowValue.'" '.$isSelected.'>'.$label.'</option>';
                    $firstRow = '';
                }
                $html .= '</select></div>';
            }
        }

        return $html;
    }
}
