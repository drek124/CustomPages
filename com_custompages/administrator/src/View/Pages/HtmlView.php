<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\View\Pages;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List view of all pages.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array
     */
    protected $items = [];

    /**
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * @var  \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * @var  \Joomla\CMS\Form\Form|null
     */
    public $filterForm;

    /**
     * @var  array
     */
    public $activeFilters = [];

    /**
     * Displays the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        /** @var \Joomla\Component\Custompages\Administrator\Model\PagesModel $model */
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }

    /**
     * Adds the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        $canDo = ContentHelper::getActions('com_custompages');

        ToolbarHelper::title(Text::_('COM_CUSTOMPAGES_PAGES_TITLE'), 'file-code');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('page.add');
        }

        if ($canDo->get('core.edit')) {
            ToolbarHelper::editList('page.edit');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('pages.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('pages.unpublish', 'JTOOLBAR_UNPUBLISH', true);

            if ((string) $this->state->get('filter.published') !== '-2') {
                ToolbarHelper::trash('pages.trash');
            }
        }

        if ((string) $this->state->get('filter.published') === '-2' && $canDo->get('core.delete')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'pages.delete', 'JTOOLBAR_DELETE');
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            ToolbarHelper::preferences('com_custompages');
        }
    }
}
