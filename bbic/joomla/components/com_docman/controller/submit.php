<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerSubmit extends ComKoowaControllerModel
{
    /**
     * A reference to the uploaded file row
     * Used to delete the file if the add action fails
     */
    protected $_uploaded;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('before.add', '_uploadFile');
        $this->addCommandCallback('after.add' , '_cleanUp');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'behaviors' => array('thumbnailable', 'findable', 'notifiable', 'editable'),
            'toolbars'  => array('submit'),
            'model'     => 'documents'
        ));

        parent::_initialize($config);
    }

    /**
     * Add the toolbar for non-authentic users too
     *
     * @param KControllerContextInterface $context
     */
    protected function _addToolbars(KControllerContextInterface $context)
    {
        if($this->getView() instanceof KViewHtml)
        {
            if($this->isDispatched())
            {
                foreach($context->toolbars as $toolbar) {
                    $this->addToolbar($toolbar);
                }

                if($toolbars = $this->getToolbars())
                {
                    $this->getView()
                        ->getTemplate()
                        ->addFilter('toolbar', array('toolbars' => $toolbars));
                };
            }
        }
    }

    protected function _actionSave(KControllerContextInterface $context)
    {
        $result = $this->execute('add', $context);

        if ($context->getResponse()->getStatusCode() === KHttpResponse::CREATED)
        {
            $route = JRoute::_('index.php?option=com_docman&view=submit&layout=success&Itemid='.$this->getRequest()->query->Itemid, false);
            $context->response->setRedirect($route);
        }

        return $result;
    }

    protected function _uploadFile(KControllerContextInterface $context)
    {
        $result = true;

        try
        {
            $data = $context->request->data;

            $translator = $this->getObject('translator');
            $page = JFactory::getApplication()->getMenu()->getItem($this->getRequest()->query->Itemid);

            if (!$page) {
                throw new RuntimeException($translator->translate('Invalid menu item'));
            }

            foreach ($this->getModel()->getTable()->getColumns() as $key => $column) {
                if (!in_array($key, array('docman_category_id', 'storage_type', 'title', 'description'))) {
                    unset($data->$key);
                }
            }

            $category = $page->params->get('category_id');

            if (!$data->docman_category_id) {
                $data->docman_category_id = $category;
            }

            $state = array(
                'id'        => $data->docman_category_id,
                'access'    => $context->user->getRoles(),
                'parent_id' => $category
            );

            if ($data->docman_category_id != $category && !$this->getObject('com://admin/docman.model.categories')->setState($state)->count()) {
                throw new KControllerExceptionRequestInvalid($translator->translate('You cannot submit documents on the selected category'));
            }

            $data->enabled = $page->params->get('auto_publish') ? 1 : 0;

            if (empty($data->storage_type)) {
                $data->storage_type = $data->storage_path_remote ? 'remote' : 'file';
            }

            if ($data->storage_type === 'file')
            {
                $file = $context->request->files->storage_path_file;

                if (empty($file) || empty($file['name'])) {
                    throw new KControllerExceptionRequestInvalid($translator->translate('You did not select a file to be uploaded.'));
                }

                $config =  array(
                    'behaviors' => array(
                        'permissible' => array(
                            'permission' => 'com://site/docman.controller.permission.submit'
                        )
                    )
                );

                $controller = $this->getObject('com:files.controller.file', $config)->container('docman-files');
                $container  = $controller->getModel()->getContainer();
                $file['name'] = $this->_getUniqueName($container, $page->params->get('folder'), $file['name']);

                $this->_uploaded = $controller
                        ->add(array(
                            'file'   => $file['tmp_name'],
                            'name'   => $file['name'],
                            'folder' => $page->params->get('folder')
                        ));

                $data->storage_path = $this->_uploaded->path;

            }
            else $data->storage_path = $data->{'storage_path_'.$data->storage_type};
        }
        catch (Exception $exception)
        {
            $message = $exception->getMessage();
            $context->getResponse()->setRedirect($this->getRequest()->getReferrer(), $message, 'error');
            $context->getResponse()->send();

            $result = false;
        }

        return $result;
    }

    /**
     * Find a unique name for the given container and folder by adding (1) (2) etc to the end of file name
     *
     * @param $container
     * @param $folder
     * @param $file
     * @return string
     */
    protected function _getUniqueName($container, $folder, $file)
    {
        $adapter   = $container->getAdapter('file');
        $folder    = $container->fullpath.(!empty($folder) ? '/'.$folder : '');
        $fileinfo  = pathinfo(' '.strtr($file, array('/' => '/ ')));
        $filename  = ltrim($fileinfo['filename']);
        $extension = $fileinfo['extension'];

        $adapter->setPath($folder.'/'.$file);

        $i = 1;
        while ($adapter->exists())
        {
            $file = sprintf('%s (%d).%s', $filename, $i, $extension);

            $adapter->setPath($folder.'/'.$file);
            $i++;
        }

        return $file;
    }

    protected function _cleanUp(KControllerContextInterface $context)
    {
        if ($context->getResponse()->getStatusCode() !== KHttpResponse::CREATED)
        {
            try
            {
                if ($this->_uploaded instanceof KModelEntityInterface) {
                    $this->_uploaded->delete();
                }

            } catch (Exception $e) {
                // Well, we tried
            }
        }
    }
}
