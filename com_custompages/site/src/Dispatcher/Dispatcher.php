<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Custompages\Site\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Site dispatcher. The site only has the `page` view, so any other view is pinned to it.
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatches the request.
     *
     * @return  void
     */
    public function dispatch()
    {
        $this->input->set('view', 'page');

        parent::dispatch();
    }
}
