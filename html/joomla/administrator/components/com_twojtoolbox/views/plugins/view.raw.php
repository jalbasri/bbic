<?php
/**
* @package     	2JToolBox
* @author       2JoomlaNet http://www.2joomla.net
* @�opyright   	Copyright (c) 2008-2012 2Joomla.net All rights reserved
* @license      released under GNU/GPL License : http://www.gnu.org/copyleft/gpl.html
* @version      $Revision: 1.0.2 $
**/

defined('_JEXEC') or die('Restricted access');

class TwoJToolboxViewPlugins extends TwojJView{

	function display($tpl = null)	{
		$this->result_install =  $this->get('Install');
		parent::display('install');
	}
}
