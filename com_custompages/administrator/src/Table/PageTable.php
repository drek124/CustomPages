<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Table class for #__custompages_pages.
 *
 * The `html` column is bound and stored untouched: no filtering happens here.
 */
class PageTable extends Table
{
    /**
     * Constructor.
     *
     * @param   DatabaseInterface         $db          Database connector object.
     * @param   ?DispatcherInterface      $dispatcher  Event dispatcher for this table.
     */
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__custompages_pages', 'id', $db, $dispatcher);

        // Lets Table::publish() work with the `state` column.
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Validates the record and normalises the alias.
     *
     * @return  boolean  True if the record is valid.
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (trim((string) $this->title) === '') {
            $this->setError(Text::_('COM_CUSTOMPAGES_ERROR_TITLE_REQUIRED'));

            return false;
        }

        if (trim((string) $this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if ($this->html === null) {
            $this->html = '';
        }

        return true;
    }

    /**
     * Stores the record, maintaining created/modified metadata and alias uniqueness.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();

        if ((int) $this->id) {
            $this->modified = $date;
        } else {
            if (empty($this->created)) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = (int) Factory::getApplication()->getIdentity()->id;
            }
        }

        $other = new static($this->_db, $this->getDispatcher());

        if ($other->load(['alias' => $this->alias]) && ((int) $other->id !== (int) $this->id || (int) $this->id === 0)) {
            $this->setError(Text::_('COM_CUSTOMPAGES_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($updateNulls);
    }
}
