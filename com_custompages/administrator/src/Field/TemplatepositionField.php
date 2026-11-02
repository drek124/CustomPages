<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Lists the module positions declared by each enabled site template, grouped by template.
 * A child template without positions of its own lists its parent's.
 */
class TemplatepositionField extends GroupedlistField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Templateposition';

    /**
     * Builds the position groups.
     *
     * @return  array
     */
    protected function getGroups()
    {
        $groups   = [];
        $lang     = Factory::getLanguage();
        $db       = $this->getDatabase();
        $clientId = 0;
        $query    = $db->getQuery(true)
            ->select($db->quoteName(['element', 'name']))
            ->from($db->quoteName('#__extensions'))
            ->where(
                [
                    $db->quoteName('type') . ' = ' . $db->quote('template'),
                    $db->quoteName('client_id') . ' = :clientId',
                    $db->quoteName('enabled') . ' = 1',
                ]
            )
            ->bind(':clientId', $clientId, ParameterType::INTEGER)
            ->order($db->quoteName('element'));

        foreach ($db->setQuery($query)->loadObjectList() as $template) {
            $positions = self::getPositions($template->element);

            if (!$positions) {
                continue;
            }

            $lang->load('tpl_' . $template->element . '.sys', JPATH_SITE)
                || $lang->load('tpl_' . $template->element . '.sys', JPATH_SITE . '/templates/' . $template->element);

            $options = [];

            foreach ($positions as $position) {
                $key   = strtoupper('TPL_' . $template->element . '_POSITION_' . $position);
                $label = $lang->hasKey($key) ? Text::_($key) . ' [' . $position . ']' : $position;

                $options[] = HTMLHelper::_('select.option', $position, $label);
            }

            $groups[Text::_($template->name)] = $options;
        }

        // Options from the XML definition (the empty "select" option) go first.
        return array_merge(parent::getGroups(), $groups);
    }

    /**
     * Reads the positions from a site template's templateDetails.xml, falling back to its parent's.
     *
     * @param   string  $template  The template folder name.
     *
     * @return  string[]
     */
    private static function getPositions(string $template): array
    {
        $file = JPATH_SITE . '/templates/' . $template . '/templateDetails.xml';
        $xml  = is_file($file) ? simplexml_load_file($file) : false;

        if (!$xml) {
            return [];
        }

        $positions = [];

        foreach ($xml->xpath('positions/position') ?: [] as $position) {
            $positions[] = trim((string) $position);
        }

        $parent = trim((string) $xml->parent);

        if (!$positions && $parent !== '' && $parent !== $template) {
            return self::getPositions($parent);
        }

        return array_values(array_unique(array_filter($positions, 'strlen')));
    }
}
