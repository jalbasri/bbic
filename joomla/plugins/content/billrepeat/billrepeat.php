<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');
jimport('joomla.utilities.date');

class plgContentBillrepeat extends JPlugin {
        /**
         * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
         * If you want to support 3.0 series you must override the constructor
         *
         * @var    boolean
         * @since  3.1
         */
        protected $autoloadLanguage = true;
 
        /**
         * Plugin method with the same name as the event will be called automatically.
         */

        // function onContentBeforeDisplay($context, &$article, &$params, $limit=0) {
        //         JError::raiseNotice( 100, 'onContentBeforeDisplay plugin fired!' );
        //         return "";
        // }

        function onContentBeforeSave($context, $article, $isNew) {
                // JError::raiseNotice( 100, 'onContentBeforeSave plugin fired!' );
                $db = JFactory::getDbo();
                $catid = $article->catid;
                $attribs_json = $article->attribs;
                $attribs = json_decode($attribs_json);


                //If Bill, add the tenant name to attribs
                if ($catid == 10) {
                    $attribs = json_decode($article->attribs);
                    $tenant_id = $attribs->billing_tenant_id;

                    
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName('username'));
                    $query->from($db->quoteName('#__users'));
                    $query->where($db->quoteName('id')." = ".$tenant_id);
                    $db->setQuery($query);
                    $result = $db->loadResult();
                    
                    $attribs->billing_tenant_name = $result;
                    $article->attribs = json_encode($attribs);

                    $article_info = $article->attribs;
                    JFactory::getApplication()->enqueueMessage("onContentBeforeSave BILL CALLED");
                }

                //Service Request, add service name+price to attribs
                elseif ($catid == 12) {
                    $service_id = $attribs->servicerequest_item;
                    
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName('attribs'));
                    $query->from($db->quoteName('#__content'));
                    $query->where($db->quoteName('id')." = ".$service_id);
                    $db->setQuery($query);
                    $result = $db->loadResult();
                    $service_attribs = json_decode($result);
                    
                    $attribs->service_price = $service_attribs->service_price;
                    $attribs->service_name = $service_attribs->service_name;
                    $article->attribs = json_encode($attribs);
                    
                }

                //If Service add the price to the title.
                //Add the service name as well as the id
                elseif ($catid == 19) {
                    $name = $attribs->service_name;
                    $price = $attribs->service_price;
                    $article->title = $name." - BD ".$price;
                }
                return true;
        }

        function onContentAfterSave($context, $article, $isNew) {
                $db = JFactory::getDbo();

                $id = intval($article->id);
                $asset_id = intval($article->asset_id);
                $catid = $article->catid;
                $attribs_json = $article->attribs;
                $attribs = json_decode($attribs_json);
                
                //Create bill automatically when servie request made
                if ($catid == 12 && $isNew) {
                    //Get the service price and name from database
                    $service_id = $attribs->servicerequest_item;
                    
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName('attribs'));
                    $query->from($db->quoteName('#__content'));
                    $query->where($db->quoteName('id')." = ".$service_id);
                    $db->setQuery($query);
                    $result = $db->loadResult();
                    $service_attribs = json_decode($result);
                    
                    $service_price = $service_attribs->service_price;
                    $service_name = $service_attribs->service_name;

                    // JFactory::getApplication()->enqueueMessage("result: ".$result);
                    // JFactory::getApplication()->enqueueMessage("service_price: ".$service_price);
                    // JFactory::getApplication()->enqueueMessage("service_name: ".$service_name);
                    // JFactory::getApplication()->enqueueMessage("sql: ".$query);
                    
                    //Lookup the tenant name in db.
                    $tenant_id = $article->created_by;
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName('username'));
                    $query->from($db->quoteName('#__users'));
                    $query->where($db->quoteName('id')." = ".$tenant_id);
                    $db->setQuery($query);
                    $result = $db->loadResult();
                    $tenant_name = $result;
                    // JFactory::getApplication()->enqueueMessage("tenant_id: ".$tenant_id);
                    // JFactory::getApplication()->enqueueMessage("tenant_name: ".$result);

                    //Set Billing Attributes
                    $billing_attribs = '{"billing_tenant_name":"'.$tenant_name.'","billing_tenant_id":"'.$tenant_id.'","billing_amount":"'.$service_price.'","billing_invoice_date":"","billing_status":"0","billing_repeatcycle":"","billing_repeatstart":"","billing_repeatend":""}';

                    //Create object to insert
                    $jt_article = JTable::getInstance('content');
                    $jt_article->title = "Service Request: ".$article->title;
                    $jt_article->alias = $tenant_id."-".$service_name."-".$new_article->id;
                    $jt_article->state = 1;
                    $jt_article->catid = 10;
                    $jt_article->access = 1;
                    $jt_article->attribs = $billing_attribs;
                    $jt_article->metadata = '{"page_title":"","author":"","robots":""}';
                    $jt_article->language = '*';
                    $jt_article->created = JFactory::getDate()->toSQL();
                    // $jt_article->publish_up = JFactory::getDate()->toSQL();
                    
                    //insert new article into database
                    if (!$jt_article->check()) {
                        JError::raiseNotice(500, $jt_article->getError());
                        // return FALSE;
                    }
                    if (!$jt_article->store(TRUE)) {
                        JError::raiseNotice(500, $jt_article->getError());
                        // return FALSE;
                    }
                }

                //Repeating Bills
                if ($catid == 10) {
                        //Check bill is set to repeat
                        if ($attribs->billing_repeat == 1) {
                                //First remove any previous repeating bills.
                                $query = $db->getQuery(true);

                                $query->delete($db->quoteName('#__content'));
                                $query->where($db->quoteName('alias')." LIKE " . $db->quote("id-".$id."-repeat%"));
                                $db->setQuery($query);
                                $deleteresult = $db->query();
                                
                                //debug
                                // $sql = $query->__toString();
                                // JFactory::getApplication()->enqueueMessage("sql: ".$sql);
                                // JFactory::getApplication()->enqueueMessage("deleteresult: ".$deleteresult);


                                $start_time = strtotime($attribs->billing_repeatstart);
                                $end_time = strtotime($attribs->billing_repeatend);
                                $cycle = $attribs->billing_repeatcycle * (24*60*60);
                                
                                if ($start_time && $end_time && $cycle) {
                                        //remove repeated billing from new_article      
                                        $new_attribs = clone $attribs;
                                        $new_attribs->billing_repeat = 0;
                                        $new_attribs_json = json_encode($new_attribs);
                                        // JFactory::getApplication()->enqueueMessage("new attribs billing_repeat: ".$new_attribs->billing_repeat);
                                        // JFactory::getApplication()->enqueueMessage("new attribs json: ".$new_attribs_json);

                                        $i = 0;
                                        while ($start_time < $end_time) {
                                                ++$i;

                                                //Joomla Table Method of Creating Article
                                                $jt_article = JTable::getInstance('content');
                                                $jt_article->title = $article->title .", Repeated Invoice #" .$i;
                                                $jt_article->alias = 'id-'.$id."-repeat-".$i;
                                                $jt_article->catid = 10;
                                                $jdate_start_time =  new JDate($start_time);
                                                $mysql_start_time = date('Y-m-d h:i:s', $start_time);
                                                $jt_article->publish_up = $mysql_start_time;
                                                $jt_article->attribs = $new_attribs_json;
                                                // $jt_article->state = 1;
                                                $jt_article->access = 1;
                                                $jt_article->metadata = '{"page_title":"","author":"","robots":""}';
                                                $jt_article->language = '*';
                                                $jt_article->created = JFactory::getDate()->toSQL();

                                                //insert new article into database
                                                if (!$jt_article->check()) {
                                                    JError::raiseNotice(500, $jt_article->getError());
                                                    // return FALSE;
                                                }
                                                if (!$jt_article->store(TRUE)) {
                                                    JError::raiseNotice(500, $jt_article->getError());
                                                    // return FALSE;
                                                }

                                                $start_time = $start_time + $cycle;
                                        }
                                        
                                } else {
                                        JError::raiseWarning( 100, 'Warning: Unable to create repeated invoices, please check repeated billing information.');
                                }
                                
                        }
                }
                // JFactory::getApplication()->enqueueMessage("articleinfo: ".$attribs->get('billing_amount'));
                // JFactory::getApplication()->enqueueMessage("articleinfo: ".$attribs->billing_amount);
                // JFactory::getApplication()->enqueueMessage("attribs billing_repeat: ".$attribs->billing_repeat);
                // JFactory::getApplication()->enqueueMessage("attribs: ".$attribs_json);
                // JFactory::getApplication()->enqueueMessage("article id: ".$article->id);
                // JError::raiseNotice( 100, 'onContentAfterSave plugin fired! ');

                return true;
        }

}

?>