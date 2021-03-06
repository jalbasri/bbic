<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Mainbody 2 columns: sidebar - content
 */
?>
<?php $id = JRequest::getVar('id');  ?>
<!--  ONE-SIDEBAR-LEFT  -->
<?php
	$app = JFactory::getApplication();
	$menu = $app->getMenu()->getActive()->id;
 ?>
<?php if ($menu != "114" &&
			$menu != "113" &&
			$menu != "199" &&
			$menu != "205" &&
			 ($id == "28" || $id == "9")) :
?>
	<div id="t3-mainbody" class="tenant-no-component container t3-mainbody">
<?php else: ?>
	<div id="t3-mainbody" class="container t3-mainbody">
<?php endif; ?>
	<div class="row">

		<!-- MAIN CONTENT -->
		<div id="t3-content" class="t3-content col-xs-12 col-sm-8 col-sm-push-4 col-md-9 col-md-push-3">

			<jdoc:include type="component" />
		</div>
		<!-- //MAIN CONTENT -->

		<!-- SIDEBAR LEFT -->
		<div class="t3-sidebar t3-sidebar-left col-xs-12 col-sm-4 col-sm-pull-8 col-md-3 col-md-pull-9 <?php $this->_c($vars['sidebar']) ?>">
			<jdoc:include type="modules" name="<?php $this->_p($vars['sidebar']) ?>" style="T3Xhtml" />
		</div>
		<!-- //SIDEBAR LEFT -->

	</div>
</div>
