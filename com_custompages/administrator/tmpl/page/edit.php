<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Custompages\Administrator\View\Page\HtmlView $this */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useStyle('com_custompages.admin-page');
?>
<form action="<?php echo Route::_('index.php?option=com_custompages&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="page-form" aria-label="<?php echo Text::_('COM_CUSTOMPAGES_PAGE_FORM_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card p-3">
        <div class="row">
            <div class="col-lg-9 custompages-editor">
                <div class="alert alert-warning">
                    <span class="icon-warning" aria-hidden="true"></span>
                    <?php echo Text::_('COM_CUSTOMPAGES_FIELD_HTML_WARNING'); ?>
                </div>
                <?php echo $this->form->renderField('html'); ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="form-vertical">
                    <legend class="visually-hidden"><?php echo Text::_('JDETAILS'); ?></legend>
                    <?php echo $this->form->renderField('state'); ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('id'); ?>
                </fieldset>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
