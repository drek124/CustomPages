<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\Field\Modal;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Page picker with Select and Clear buttons, opening the pages list in a modal
 * (the same field type com_content uses for articles).
 */
class PageField extends ModalSelectField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Modal_Page';

    /**
     * Sets up the field from its XML element.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value.
     *
     * @return  boolean  True on success.
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $result = parent::setup($element, $value, $group);

        if (!$result) {
            return $result;
        }

        Factory::getApplication()->getLanguage()->load('com_custompages', JPATH_ADMINISTRATOR);

        $urlSelect = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $urlSelect->setQuery([
            'option'                => 'com_custompages',
            'view'                  => 'pages',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);

        $this->urls['select']        = (string) $urlSelect;
        $this->modalTitles['select'] = Text::_('COM_CUSTOMPAGES_SELECT_PAGE');

        $this->hint = $this->hint ?: Text::_('COM_CUSTOMPAGES_SELECT_PAGE');

        return $result;
    }

    /**
     * Returns the title of the selected page, shown in the field.
     *
     * @return  string
     */
    protected function getValueTitle()
    {
        $value = (int) $this->value ?: '';
        $title = '';

        if ($value) {
            try {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('title'))
                    ->from($db->quoteName('#__custompages_pages'))
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':id', $value, ParameterType::INTEGER);

                $title = $db->setQuery($query)->loadResult();
            } catch (\Throwable $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            }
        }

        return $title ?: $value;
    }
}
