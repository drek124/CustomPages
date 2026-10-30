<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Site\View\Page;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Outputs the stored HTML document as the entire response.
 *
 * The template and the Joomla document are never rendered: output buffers are discarded,
 * headers are sent, the HTML is echoed untouched and the application is closed.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Sends the page and terminates the request.
     *
     * @param   string  $tpl  Unused.
     *
     * @return  void
     *
     * @throws  \Exception  404 if the page is missing or unpublished, 403 if the user lacks access.
     */
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        /** @var \Joomla\Component\Custompages\Site\Model\PageModel $model */
        $model = $this->getModel();
        $page  = $model->getItem();

        if ($page === null || (int) $page->state !== 1) {
            throw new \Exception(Text::_('COM_CUSTOMPAGES_ERROR_PAGE_NOT_FOUND'), 404);
        }

        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());

        if (!\in_array((int) $page->access, $levels, true)) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // Optional extra: Cache-Control from the menu item. Restricted pages must never be cached publicly.
        $cacheControl = trim((string) $model->getState('params')->get('cache_control', ''));

        if ((int) $page->access !== 1) {
            $cacheControl = 'private, no-cache';
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $app->setHeader('Content-Type', 'text/html; charset=utf-8', true);

        if ($cacheControl !== '') {
            $app->setHeader('Cache-Control', $cacheControl, true);
        }

        $app->sendHeaders();

        echo $page->html;

        $app->close();
    }
}
