<?php

/**
 * @package     com_custompages
 *
 * @copyright   (C) 2026 drek124
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Installer\InstallerScript;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Refuses to install on unsupported versions. The menu item's page picker
 * extends ModalSelectField, which was added in Joomla 5.0.
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class com_custompagesInstallerScript extends InstallerScript
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var  string
     */
    protected $minimumJoomla = '5.0.0';

    /**
     * @var  string
     */
    protected $minimumPhp = '8.1.0';
}
