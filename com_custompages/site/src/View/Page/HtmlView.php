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
 * Outputs the stored page as the entire response.
 *
 * The template and the Joomla document are never rendered: output buffers are discarded,
 * the page is run as PHP, headers are sent, its output is echoed and the application is closed.
 * A page without `<?php` tags is plain HTML and comes out byte for byte.
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

        $output = self::runPhp($page, $app, $model->getState('params'));

        // The page may have chosen its own Content-Type (e.g. JSON); only default it.
        if (!self::hasContentType($app)) {
            $app->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        }

        if ($cacheControl !== '') {
            $app->setHeader('Cache-Control', $cacheControl, true);
        }

        $app->sendHeaders();

        echo $output;

        $app->close();
    }

    /**
     * Runs the page as PHP and returns what it printed.
     *
     * The source is evaluated like a PHP template file: it starts in HTML mode, so code goes
     * inside `<?php ... ?>` tags. `$app`, `$page` and `$params` (menu item params) are in scope.
     * Anything it throws, including a \ParseError, propagates to Joomla's error page.
     *
     * @param   object                                           $page    The page row.
     * @param   \Joomla\CMS\Application\CMSApplicationInterface  $app     The application.
     * @param   \Joomla\Registry\Registry                         $params  The menu item params.
     *
     * @return  string
     */
    private static function runPhp(object $page, $app, $params): string
    {
        ob_start();

        try {
            (static function ($app, $page, $params) {
                eval('?>' . $page->html);
            })($app, $page, $params);
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        // The script may have opened buffers of its own; collapse them all into the output.
        $output = '';

        while (ob_get_level()) {
            $output = ob_get_clean() . $output;
        }

        return $output;
    }

    /**
     * Whether a Content-Type header was set, through Joomla or PHP's header().
     *
     * @param   \Joomla\CMS\Application\CMSApplicationInterface  $app  The application.
     *
     * @return  boolean
     */
    private static function hasContentType($app): bool
    {
        foreach ($app->getHeaders() as $header) {
            if (strcasecmp($header['name'], 'Content-Type') === 0) {
                return true;
            }
        }

        foreach (headers_list() as $line) {
            if (stripos($line, 'Content-Type:') === 0) {
                return true;
            }
        }

        return false;
    }
}
