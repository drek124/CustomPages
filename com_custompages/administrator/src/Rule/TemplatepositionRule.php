<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\Rule;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Makes the position required, but only when a template style is selected.
 *
 * A plain `required="true"` can't be used: Joomla validates fields hidden by `showon` too,
 * so it would block saving menu items set to "No template".
 */
class TemplatepositionRule extends FormRule
{
    /**
     * Tests the position value.
     *
     * @param   \SimpleXMLElement  $element  The field's XML element.
     * @param   mixed              $value    The position.
     * @param   string             $group    The field's group (`params`).
     * @param   ?Registry          $input    All submitted form data.
     * @param   ?Form              $form     The form.
     *
     * @return  boolean
     */
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        $style = $input ? (string) $input->get(($group ? $group . '.' : '') . 'template_style', 'none') : 'none';

        return $style === 'none' || $style === '' || trim((string) $value) !== '';
    }
}
