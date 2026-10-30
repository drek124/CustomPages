<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\View\Page;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Edit view for a single page.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * @var  object
     */
    protected $item;

    /**
     * @var  \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * Displays the view.
     *
     * @param   string  $tpl  The name of the template file to parse.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        /** @var \Joomla\Component\Custompages\Administrator\Model\PageModel $model */
        $model = $this->getModel();

        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Adds the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew = (int) $this->item->id === 0;
        $canDo = ContentHelper::getActions('com_custompages');

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_CUSTOMPAGES_PAGE_NEW_TITLE' : 'COM_CUSTOMPAGES_PAGE_EDIT_TITLE'),
            'file-code'
        );

        if ($isNew ? $canDo->get('core.create') : $canDo->get('core.edit')) {
            ToolbarHelper::apply('page.apply');
            ToolbarHelper::save('page.save');
        }

        if ($canDo->get('core.create')) {
            ToolbarHelper::save2new('page.save2new');
        }

        ToolbarHelper::cancel('page.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
