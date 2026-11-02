<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Site\View\Page;

use Joomla\CMS\Event\Module\AfterCleanModuleListEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Outputs the stored page, either as the entire response or inside a template position.
 *
 * With "No template" (the default) the template and the Joomla document are never rendered:
 * output buffers are discarded, the page is run as PHP, headers are sent, its output is echoed
 * and the application is closed. A page without `<?php` tags is plain HTML and comes out byte for byte.
 *
 * With a template style, Joomla renders the page normally and the page's output is placed,
 * without module chrome, in the chosen module position.
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

        $params = $model->getState('params');

        // Optional extra: Cache-Control from the menu item. Restricted pages must never be cached publicly.
        $cacheControl = trim((string) $params->get('cache_control', ''));

        if ((int) $page->access !== 1) {
            $cacheControl = 'private, no-cache';
        }

        $templateStyle = (string) $params->get('template_style', 'none');

        if ($templateStyle !== 'none' && $templateStyle !== '') {
            $this->displayInTemplate($page, $app, $params, (int) $templateStyle, $cacheControl);

            return;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $output = self::runPhp($page, $app, $params);

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
     * Lets Joomla render the template and places the page's output in the chosen module position.
     *
     * @param   object                                     $page          The page row.
     * @param   \Joomla\CMS\Application\SiteApplication    $app           The application.
     * @param   \Joomla\Registry\Registry                   $params        The menu item params.
     * @param   integer                                    $styleId       Template style ID, 0 for the default one.
     * @param   string                                     $cacheControl  Cache-Control header value, or ''.
     *
     * @return  void
     */
    private function displayInTemplate(object $page, $app, $params, int $styleId, string $cacheControl): void
    {
        if ($styleId > 0) {
            $this->applyTemplateStyle($app, $styleId);
        }

        $this->setDocumentTitle((string) $params->get('page_title', '') ?: $page->title);

        if ($cacheControl !== '') {
            // Without this Joomla adds its own no-cache Cache-Control header next to ours.
            $app->allowCache(true);
            $app->setHeader('Cache-Control', $cacheControl, true);
        }

        $output   = self::runPhp($page, $app, $params);
        $position = strtolower(trim((string) $params->get('template_position', '')));

        // The form requires a position; if it is missing anyway, fall back to the component area.
        if ($position === '') {
            echo $output;

            return;
        }

        // Add the page to the module list as an already rendered module, so the position counts
        // as non-empty (countModules) and the output is printed as is ("none" chrome).
        $module                  = new \stdClass();
        $module->id              = 0;
        $module->title           = $page->title;
        $module->module          = 'mod_custom';
        $module->name            = 'custom';
        $module->position        = $position;
        $module->content         = $output;
        $module->contentRendered = true;
        $module->showtitle       = 0;
        $module->control         = '';
        $module->params          = json_encode(['style' => 'System-none']);
        $module->menuid          = 0;
        $module->style           = null;

        $app->getDispatcher()->addListener(
            'onAfterCleanModuleList',
            static function (AfterCleanModuleListEvent $event) use ($module) {
                $modules   = $event->getModules();
                $modules[] = $module;

                $event->updateModules($modules);
            }
        );
    }

    /**
     * Switches the site to the given template style for this request.
     *
     * Joomla picks the template before the component runs, so the template from the menu item
     * (or the default style) is replaced here and the new template's asset registry is added.
     * Unknown or disabled styles are ignored.
     *
     * @param   \Joomla\CMS\Application\SiteApplication  $app      The application.
     * @param   integer                                  $styleId  The template style ID.
     *
     * @return  void
     */
    private function applyTemplateStyle($app, int $styleId): void
    {
        /** @var \Joomla\Component\Custompages\Site\Model\PageModel $model */
        $model = $this->getModel();
        $style = $model->getTemplateStyle($styleId);

        if (!$style) {
            return;
        }

        $previous = $app->getTemplate();

        $app->setTemplate($style);

        // Same template, other style: its assets are already registered.
        if ($style->template === $previous) {
            return;
        }

        $registry = $this->getDocument()->getWebAssetManager()->getRegistry();

        if ($style->parent) {
            $registry->addTemplateRegistryFile($style->parent, 0);
        }

        $registry->addTemplateRegistryFile($style->template, 0);
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
        // Joomla's own buffers (template mode) are below this level and must be left alone.
        $level = ob_get_level();

        ob_start();

        try {
            (static function ($app, $page, $params) {
                eval('?>' . $page->html);
            })($app, $page, $params);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        // The script may have opened buffers of its own; collapse them all into the output.
        $output = '';

        while (ob_get_level() > $level) {
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
