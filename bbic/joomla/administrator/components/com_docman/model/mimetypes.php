<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanModelMimetypes extends KModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('mimetype', 'string')
            ->insert('extension', 'string');
    }

    protected function _buildQueryWhere(KDatabaseQueryInterface $query)
    {
        parent::_buildQueryWhere($query);

        $state = $this->getState();
        
        if ($state->mimetype) {
            $query->where('mimetype IN :mimetype')->bind(array('mimetype' => (array) $state->mimetype));
        }

        if ($state->extension) {
        	$query->where('extension IN :extension')->bind(array('extension' => (array) $state->extension));
        }
    }
}
