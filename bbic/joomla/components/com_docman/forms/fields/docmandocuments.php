<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class JFormFieldDocmandocuments extends JFormField
{
    protected $type = 'Docmandocuments';

    protected function getInput()
    {
        if (!class_exists('Koowa')) {
            return '';
        }

        $value = $this->value;
        $el_name = $this->name;

        KObjectManager::getInstance()->getObject('translator')->load('com://admin/docman');

        $view = KObjectManager::getInstance()->getObject('com://admin/docman.view.default');
        $template = $view->getTemplate()
            ->addFilter('style')
            ->addFilter('script');

        $string = "
        <?= helper('bootstrap.load') ?>
        <?= helper('com://admin/docman.listbox.documents', array(
            'name' => \$el_name,
            'attribs' => array('id' => 'docman_document_select'),
            'selected' => \$value,
            'deselect' => false,
            'autocomplete' => true,
            'prompt' => translate('Search for a document'),
            'value' => 'slug',
            'label' => 'title'
        )) ?>
        ";

        return $template->loadString($string, 'php')
            ->render(array(
                'el_name' => $el_name,
                'value'   => $value
            ));
    }
}
