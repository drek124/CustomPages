<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Model for editing a single page.
 */
class PageModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_custompages.page';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     */
    protected $text_prefix = 'COM_CUSTOMPAGES_PAGE';

    /**
     * Returns the page edit form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data.
     *
     * @return  Form|boolean  The form, or false on failure.
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_custompages.page',
            'page',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        $user = $this->getCurrentUser();

        if (!$user->authorise('core.edit.state', 'com_custompages')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Saves the page.
     *
     * Browsers submit textarea values with CRLF line breaks (HTML spec form encoding), while
     * CodeMirror holds the document with LF. Undoing that conversion stores exactly what the
     * editor contained; nothing else in the HTML is touched.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     */
    public function save($data)
    {
        if (isset($data['html']) && \is_string($data['html'])) {
            $data['html'] = str_replace("\r\n", "\n", $data['html']);
        }

        return parent::save($data);
    }

    /**
     * Returns the data that should be injected into the form.
     *
     * @return  mixed
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_custompages.edit.page.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_custompages.page', $data);

        return $data;
    }
}
