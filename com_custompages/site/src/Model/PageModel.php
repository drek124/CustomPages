<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Loads a single page for the frontend.
 */
class PageModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var    string
     */
    protected $_context = 'com_custompages.page';

    /**
     * Auto-populates the model state from the request and the active menu item.
     *
     * @return  void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        $this->setState('page.id', $app->getInput()->getInt('id'));
        $this->setState('params', $app->getParams());
    }

    /**
     * Returns the page row, or null if no page has that ID.
     *
     * Publishing and access are checked by the view so it can choose between 404 and 403.
     *
     * @param   integer  $pk  The page ID.
     *
     * @return  object|null
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('page.id'));

        if ($this->_item === null) {
            $this->_item = [];
        }

        if (!\array_key_exists($pk, $this->_item)) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'title', 'alias', 'html', 'state', 'access']))
                ->from($db->quoteName('#__custompages_pages'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $pk, ParameterType::INTEGER);

            $this->_item[$pk] = $pk > 0 ? ($db->setQuery($query)->loadObject() ?: null) : null;
        }

        return $this->_item[$pk];
    }
}
