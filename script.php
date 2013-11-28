<?php

// No direct access to this file
defined('_JEXEC') or die;

class plgCoursemanPaymentRealexInstallerScript {
    /*
     * The release value to be displayed and checked against throughout this file.
     */

    private $release = '1.0.0';

    /*
     * Find mimimum required joomla version for this extension. It will be read from the version attribute (install tag) in the manifest file
     */
    private $minimum_joomla_release = '3.0.0';

    function install($parent) {

        // You can have the backend jump directly to the newly installed component configuration page
        // $parent->getParent()->setRedirectURL('index.php?option=com_democompupdate');
        // Shows after install
        echo '<div class="well"><img style="float: left; margin-left: 15px; margin-right: 15px; margin-bottom: 10px;" src="' . JURI::root() . 'components/com_courseman/assets/images/' . 'logo.png' . '" alt="Profinvent logo" />';
        echo '<div style="width: 50em; margin: 0pt; padding: 0.5em;">';
        echo '<p>' . JText::_('PLG_COURSEMAN_PAYMENTREALEX_ENABLE_PLUGIN_MSG') . '</p>';
        echo '<p><a style="font-weight: bold; color: #FEA23B; font-size: 1.1em;" href="' . JRoute::_('index.php?option=com_plugins&view=plugins') . '" title="">Administration</a></p>';
        echo '</div></div>';
    }

    function uninstall($parent) {
        jimport('joomla.application.component.controller');
        if (JComponentHelper::isEnabled('com_courseman')) {
            $app = JFactory::getApplication();
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('extension_id AS eid');
            $query->from('#__courseman_addons AS x');
            $query->where('sysname LIKE ' . $db->quote('plg_courseman_payment_realex', false));
            $db->setQuery($query);
            $eid = $db->loadResult();
            if (count($eid) > 0) {
                $query = $db->getQuery(true);
                $query->delete($db->quoteName('#__courseman_addons'));
                $query->where('sysname LIKE ' . $db->quote('plg_courseman_payment_realex', false));
                $db->setQuery($query);

                try {
                    $result = $db->execute(); // $db->execute(); for Joomla 3.0.
                } catch (Exception $e) {
                    $app->enqueueMessage(JText::_('PLG_COURSEMAN_PAYMENTREALEX_UNINSTALL_FAILED'), 'error');
                }
            }
        }
    }

    function update($parent) {
        
    }

    function preflight($type, $parent) {
        // this component does not work with Joomla releases prior to 1.6
        // abort if the current Joomla release is older
        $jversion = new JVersion();

        // Extract the version number from the manifest. This will overwrite the 1.0 value set above
        $this->release = $parent->get("manifest")->version;

        // Find mimimum required joomla version
        $this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;

        if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, 'lt')) {
            Jerror::raiseWarning(null, 'Cannot install this plugin in a Joomla! release prior to ' . $this->minimum_joomla_release);
            return false;
        }

        // abort if the component being installed is not newer than the currently installed version
        //if ( $type == 'update' ) {
        //   $oldRelease = $this->getParam('version');
        //   $rel = $oldRelease . ' to ' . $this->release;
        //   if ( version_compare( $this->release, $oldRelease, 'le' ) ) {
        //       Jerror::raiseWarning(null, 'Incorrect version sequence. Cannot upgrade ' . $rel);
        //       return false;
        //   }
        //}
        else {
            $rel = $this->release;
        }
    }

    function postflight($type, $parent) {
        // always create or modify these parameters
    }

    /*
     * get a variable from the manifest file (actually, from the manifest cache).
     */

    function getParam($name) {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "Courseman - PaymentRealex"');
        $manifest = json_decode($db->loadResult(), true);
        return $manifest[$name];
    }

}