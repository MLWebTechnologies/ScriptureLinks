<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
class JFormFieldSLHeader extends JFormField {
	var	$type = 'header';
	protected function getInput()
	{
		$document = & JFactory::getDocument();
		$document->addStyleSheet(JURI::root(true).'/plugins/content/scripturelinks/scripturelinks/elements/slheader.css');
		return '<div class="paramHeaderContainer"><div class="paramHeaderContent">'.JText::_($this->value).'</div><div class="slclr"></div></div>';
  }
}