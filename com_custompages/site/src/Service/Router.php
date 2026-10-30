<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * SEF router. A page linked from a menu item routes to the menu item's path only;
 * other links get an `id-alias` segment.
 */
class Router extends RouterView
{
    /**
     * @var  DatabaseInterface
     */
    private $db;

    /**
     * Constructor.
     *
     * @param   SiteApplication            $app              The application object.
     * @param   AbstractMenu               $menu             The menu object to work with.
     * @param   ?CategoryFactoryInterface  $categoryFactory  Unused; passed by the core RouterFactory.
     * @param   ?DatabaseInterface         $db               The database object.
     */
    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        ?CategoryFactoryInterface $categoryFactory = null,
        ?DatabaseInterface $db = null
    ) {
        $this->db = $db ?? Factory::getContainer()->get(DatabaseInterface::class);

        $page = new RouterViewConfiguration('page');
        $page->setKey('id');
        $this->registerView($page);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Returns the segment for a page.
     *
     * @param   string|int  $id     The page ID, optionally as `id:alias`.
     * @param   array       $query  The request being built.
     *
     * @return  array  Array of [id => segment].
     */
    public function getPageSegment($id, $query)
    {
        $id = (string) $id;

        if (strpos($id, ':') === false) {
            $pk      = (int) $id;
            $dbQuery = $this->db->getQuery(true)
                ->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__custompages_pages'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $pk, ParameterType::INTEGER);
            $alias   = (string) $this->db->setQuery($dbQuery)->loadResult();
            $id      = $alias !== '' ? $pk . ':' . $alias : (string) $pk;
        }

        return [(int) $id => $id];
    }

    /**
     * Returns the page ID for a segment such as `12-my-page`.
     *
     * @param   string  $segment  The segment.
     * @param   array   $query    The request being parsed.
     *
     * @return  integer|false
     */
    public function getPageId($segment, $query)
    {
        $id = (int) $segment;

        return $id > 0 ? $id : false;
    }
}
