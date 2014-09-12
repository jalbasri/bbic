<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Create some shortcuts.
$params		= &$this->item->params;
$n			= count($this->items);
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

//Check for Tenant - ADJUST GROUP AND USER IDS FOR DIFFERENT SERVER. 

// var_dump($this);

$isTenant = in_array("10", array_values(JFactory::getUser()->groups));
$restrictView = false;
if ($this->items[0]){
	$catid = $this->items[0]->catid;
	$restrictView = (($catid == "9") | ($catid == "10") | ($catid == "12"))  && $isTenant;
}
//Table header settings
$list_show_servicerequest_item = false;
$list_show_servicerequest_approval = false;
$list_show_billing_name = false;
$list_show_billing_status = false;
$list_show_billing_price = false;

if ($catid == "8") {
	//var_dump($this);
	$list_show_category_title = true;
}

//IF CATEGORY IS NEWS OR COMPANY PROFILE
if ($catid == 8 || 9) {

}

//IF CATEGORY IS SERVICE REQUEST OR BILL
if ($catid == 12 || 10) {
	$this->params->set("list_show_date", "created");
	$this->params->set("list_show_author", "0");
	$this->params->set("list_show_hits", "0");
}

if ($catid == 12) {
	$list_show_servicerequest_item = true;
	$list_show_servicerequest_approval = true;
}

if ($catid == 10) {
	$list_show_billing_name = !$restrictView;
	$list_show_billing_status = true;
	$list_show_billing_price = true;
}



// Check for at least one editable article
$isEditable = false;

if (!empty($this->items))
{
	foreach ($this->items as $article)
	{
		if ($article->params->get('access-edit'))
		{
			$isEditable = true;
			break;
		}
	}
}
?>

<?php if (empty($this->items)) : ?>

	<?php if ($this->params->get('show_no_articles', 1)) : ?>
		<p><?php echo JText::_('COM_CONTENT_NO_ARTICLES'); ?></p>
	<?php endif; ?>

<?php else : ?>

	<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
		<?php if ($this->params->get('show_headings') || $this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit')) :?>
			<fieldset class="filters btn-toolbar clearfix">
				<?php if ($this->params->get('filter_field') != 'hide') :?>
					<div class="btn-group">
						<label class="filter-search-lbl element-invisible" for="filter-search">
							<?php echo JText::_('COM_CONTENT_'.$this->params->get('filter_field').'_FILTER_LABEL').'&#160;'; ?>
						</label>
						<input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox" onchange="document.adminForm.submit();" title="<?php echo JText::_('COM_CONTENT_FILTER_SEARCH_DESC'); ?>" placeholder="<?php echo JText::_('COM_CONTENT_'.$this->params->get('filter_field').'_FILTER_LABEL'); ?>" />
					</div>
				<?php endif; ?>
				<?php if ($this->params->get('show_pagination_limit')) : ?>
					<div class="btn-group pull-right">
						<label for="limit" class="element-invisible">
							<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
						</label>
						<?php echo $this->pagination->getLimitBox(); ?>
					</div>
				<?php endif; ?>

				<input type="hidden" name="filter_order" value="" />
				<input type="hidden" name="filter_order_Dir" value="" />
				<input type="hidden" name="limitstart" value="" />
				<input type="hidden" name="task" value="" />
			</fieldset>
		<?php endif; ?>

		<!-- SET UP THE TABLE -->

		<table class="category table table-striped table-bordered table-hover">
			<?php if ($this->params->get('show_headings')) : ?>
				<thead>
					<tr>
						<th id="categorylist_header_title">
							<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<?php if ($date = $this->params->get('list_show_date')) : ?>
							<th id="categorylist_header_date">
								<?php if ($date == "created") : ?>
									<?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.created', $listDirn, $listOrder); ?>
								<?php elseif ($date == "modified") : ?>
									<?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.modified', $listDirn, $listOrder); ?>
								<?php elseif ($date == "published") : ?>
									<?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.publish_up', $listDirn, $listOrder); ?>
								<?php endif; ?>
							</th>
						<?php endif; ?>
						<?php if ($this->params->get('list_show_author')) : ?>
							<th id="categorylist_header_author">
								<?php echo JHtml::_('grid.sort', 'JAUTHOR', 'author', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						<?php if ($this->params->get('list_show_hits')) : ?>
							<th id="categorylist_header_hits">
								<?php echo JHtml::_('grid.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>

						<!--SERVICE REQUEST HEADINGS -->
						<?php if ($list_show_servicerequest_item) : ?>
							<th id="categorylist_header_servicerequest_item"><?php echo JText::_('TPL_EXTRAFIELDS_SERVICEREQUEST_ITEM'); ?></th>
						<?php endif; ?>
						<?php if ($list_show_servicerequest_approval) : ?>
							<th id="categorylist_header_servicerequest_approval"><?php echo JText::_('TPL_EXTRAFIELDS_SERVICEREQUEST_APPROVAL'); ?></th>
						<?php endif; ?>

						<!-- BILLING HEADINGS -->
						<?php if ($list_show_billing_name) : ?>
							<th id="categorylist_header_billing_name">
								<!--<?php echo JText::_('TPL_EXTRAFIELDS_BILLING_INVOICEE'); ?>-->
								<?php echo JHtml::_('grid.sort', 'TPL_EXTRAFIELDS_BILLING_INVOICEE', "", $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						<?php if ($list_show_billing_price) : ?>
							<th id="categorylist_header_billing_price">
								<!--<?php echo JText::_('TPL_EXTRAFIELDS_BILLING_AMOUNT'); ?>-->
								<?php echo JHtml::_('grid.sort', 'TPL_EXTRAFIELDS_BILLING_AMOUNT', "", $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
						<?php if ($list_show_billing_status) : ?>
							<th id="categorylist_header_billing_status">
								<!--<?php echo JText::_('TPL_EXTRAFIELDS_BILLING_STATUS'); ?>-->
								<?php echo JHtml::_('grid.sort', 'TPL_EXTRAFIELDS_BILLING_STATUS', "", $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>

						<!-- NEWS HEADINGS -->
						<?php if ($list_show_category_title) :?>
							<th id="categorylist_header_billing_status">
								<?php echo JText::_('J3_LISTHEADING_CATEGORY'); ?>
							</th>
						<?php endif; ?>

						<?php if ($isEditable) : ?>
							<th id="categorylist_header_edit"><?php echo JText::_('J3_EDITOR_EDIT_ITEM'); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
			<?php endif; ?>
			<tbody>

			<!-- FILL IN THE TABLE ROWS -->
			<?php foreach ($this->items as $i => $article) : ?>
				<?php 
					$attribs = new JRegistry($article->attribs);
					// var_dump($attribs);
					// var_dump($attribs->get('servicerequest_approval'))
				 ?>
				<!-- CHECK IF IS A TENANT VIEWING A COMPANY PROFILE -->
				<?php if ($restrictView) : ?>
					<?php 
						$username = JFactory::getUser()->name;
						$userid = JFactory::getUser()->id;
						$showarticle = false;
						if ($catid == 10) {
							if ($attribs->get('billing_tenant_name') == $username || $attribs->get('billing_tenant_id') == $userid)
								$showarticle = true;
						} else {
							if($this->items[$i]->created_by == $userid)
								$showarticle = true;
						}
					?>

					<!-- ONLY PRINT IF ARTICLE IS OWNED BY THE CURRENT USER -->
					<?php if ($showarticle) : ?>			
								
						<?php if ($this->items[$i]->state == 0) : ?> <!--remove this to print unpublished items -->
							<tr class="system-unpublished cat-list-row<?php echo $i % 2; ?>">
						<?php else: ?>
							<tr class="cat-list-row<?php echo $i % 2; ?>" >
						<?php endif; ?>
								<td headers="categorylist_header_title" class="list-title">
									<?php if (in_array($article->access, $this->user->getAuthorisedViewLevels())) : ?>
										<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid)); ?>">
											<?php echo $this->escape($article->title); ?>
										</a>
									<?php else: ?>
										<?php
											echo $this->escape($article->title).' : ';
											$menu		= JFactory::getApplication()->getMenu();
											$active		= $menu->getActive();
											$itemId		= $active->id;
											$link = JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId);
											$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug));
											$fullURL = new JUri($link);
											$fullURL->setVar('return', base64_encode($returnURL));
										?>
										<a href="<?php echo $fullURL; ?>" class="register">
											<?php echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE'); ?>
										</a>
									<?php endif; ?>
									<?php if ($article->state == 0) : ?>
										<span class="list-published label label-warning">
											<?php echo JText::_('JUNPUBLISHED'); ?>
										</span>
									<?php endif; ?>
									<?php if (strtotime($article->publish_up) > strtotime(JFactory::getDate())) : ?>
										<span class="list-published label label-warning">
											<?php echo JText::_('JNOTPUBLISHEDYET'); ?>
										</span>
									<?php endif; ?>
									<?php if ((strtotime($article->publish_down) < strtotime(JFactory::getDate())) && $article->publish_down != '0000-00-00 00:00:00') : ?>
										<span class="list-published label label-warning">
											<?php echo JText::_('JEXPIRED'); ?>
										</span>
									<?php endif; ?>
								</td>
								<?php if ($this->params->get('list_show_date')) : ?>
									<td headers="categorylist_header_date" class="list-date small">
										<?php
											echo JHtml::_('date', $article->displayDate, $this->escape($this->params->get('date_format', JText::_('DATE_FORMAT_LC3')))); 
										?>
							         </td>		
							    <?php endif; ?>
								<?php if ($this->params->get('list_show_author', 1)) : ?>
									<td headers="categorylist_header_author" class="list-author">
							      		<?php if (!empty($article->author) || !empty($article->created_by_alias)) : ?>
							      			<?php $author = $article->author ?>
							      			<?php $author = ($article->created_by_alias ? $article->created_by_alias : $author);?>
							      			<?php if (!empty($article->contact_link) && $this->params->get('link_author') == true) : ?>
							      				<?php echo JText::sprintf('COM_CONTENT_WRITTEN_BY', JHtml::_('link', $article->contact_link, $author)); ?>
							      			<?php else: ?>
							      				<?php echo JText::sprintf('COM_CONTENT_WRITTEN_BY', $author); ?>
							      			<?php endif; ?>
							      		<?php endif; ?>
							      	</td>
								<?php endif; ?>
						    	<?php if ($this->params->get('list_show_hits', 1)) : ?>
						      		<td headers="categorylist_header_hits" class="list-hits">
						      			<span class="badge badge-info">
						      				<?php echo JText::sprintf('JGLOBAL_HITS_COUNT', $article->hits); ?>
						      			</span>
						      		</td>
						      	<?php endif; ?>
						      	<?php if ($list_show_servicerequest_item) :?>
						      		<td headers="categorylist_header_servicerequest_item" class="list-servicerequest-item">
										<?php echo $attribs->get('service_name'); ?>
							      	</td>
						      	<?php endif; ?>
						      	<?php if ($list_show_servicerequest_approval) :?>
						      	<td headers="categorylist_header_servicerequest_approval" class="list-servicerequest-approval">
						      		<?php
						      			$servicerequest_approval = $attribs->get('servicerequest_approval');
						      			switch ($servicerequest_approval) {
						      				case '0':
						      					echo "Pending";
						      					break;
						      				case '1':
						      					echo "Approved";
						      					break;
						      				case '2':
						      					echo "Denied";
						      					break;
						      				
						      				default:
						      					
						      					break;
						      			};
						      		?>
						      	</td>
						      	<?php endif; ?>
						      	<?php if ($list_show_billing_name) : ?>
							    	<td headers="categorylist_header_billing_name" class="list-billing-name">
							      		<?php echo $attribs->get('tenant_name'); ?>
							      	</td>
								<?php endif; ?>
								<?php if ($list_show_billing_price) : ?>
							    	<td headers="categorylist_header_billing_price" class="list-billing-price">
							      		<?php echo $attribs->get('billing_amount'); ?>
							      	</td>
								<?php endif; ?>
						    	<?php if ($list_show_billing_status) : ?>
									<td headers="categorylist_header_billing_status" class="list-billing-status">
							      		<?php 
							      			$billing_status = $attribs->get('billing_status'); 
							      			switch ($billing_status) {
							      				case '0':
							      					echo "Unpaid";
							      					break;
							      				case '1':
							      					echo "Paid";
							      					break;
							      				
							      				default:
							      					break;
							      			};
							      		?>
							      	</td>
						      	<?php endif; ?>
								<?php if ($isEditable) : ?>
									<td headers="categorylist_header_edit" class="list-edit">
										<?php if ($article->params->get('access-edit')) : ?>
											<?php echo JHtml::_('icon.edit', $article, $params); ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
					</tr>
					<?php endif; ?>
					<?php else: ?> 

					<!-- DO NOT RESTRICT ITEMS -->
					<?php if ($this->items[$i]->state == 0) : ?>
						<tr class="system-unpublished cat-list-row<?php echo $i % 2; ?>">
					<?php else: ?>
						<tr class="cat-list-row<?php echo $i % 2; ?>" >
					<?php endif; ?>
							<td headers="categorylist_header_title" class="list-title">
								<?php if (in_array($article->access, $this->user->getAuthorisedViewLevels())) : ?>
								<a href="<?php echo JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid)); ?>">
									<?php echo $this->escape($article->title); ?>
								</a>
								<?php else: ?>
									<?php
									echo $this->escape($article->title).' : ';
									$menu		= JFactory::getApplication()->getMenu();
									$active		= $menu->getActive();
									$itemId		= $active->id;
									$link = JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId);
									$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug));
									$fullURL = new JUri($link);
									$fullURL->setVar('return', base64_encode($returnURL));
									?>
									<a href="<?php echo $fullURL; ?>" class="register">
										<?php echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE'); ?>
									</a>
								<?php endif; ?>
								<?php if ($article->state == 0) : ?>
									<span class="list-published label label-warning">
										<?php echo JText::_('JUNPUBLISHED'); ?>
									</span>
								<?php endif; ?>
								<?php if (strtotime($article->publish_up) > strtotime(JFactory::getDate())) : ?>
									<span class="list-published label label-warning">
										<?php echo JText::_('JNOTPUBLISHEDYET'); ?>
									</span>
								<?php endif; ?>
								<?php if ((strtotime($article->publish_down) < strtotime(JFactory::getDate())) && $article->publish_down != '0000-00-00 00:00:00') : ?>
									<span class="list-published label label-warning">
										<?php echo JText::_('JEXPIRED'); ?>
									</span>
								<?php endif; ?>
							</td>
							<?php if ($this->params->get('list_show_date')) : ?>
								<td headers="categorylist_header_date" class="list-date small">
									<?php
									echo JHtml::_(
									              'date', $article->displayDate,
									              $this->escape($this->params->get('date_format', JText::_('DATE_FORMAT_LC3')))
									              ); ?>
									          </td>
									      <?php endif; ?>
									      <?php if ($this->params->get('list_show_author', 1)) : ?>
									      	<td headers="categorylist_header_author" class="list-author">
									      		<?php if (!empty($article->author) || !empty($article->created_by_alias)) : ?>
									      			<?php $author = $article->author ?>
									      			<?php $author = ($article->created_by_alias ? $article->created_by_alias : $author);?>
									      			<?php if (!empty($article->contact_link) && $this->params->get('link_author') == true) : ?>
									      				<?php echo JText::sprintf('COM_CONTENT_WRITTEN_BY', JHtml::_('link', $article->contact_link, $author)); ?>
									      			<?php else: ?>
									      				<?php echo JText::sprintf('COM_CONTENT_WRITTEN_BY', $author); ?>
									      			<?php endif; ?>
									      		<?php endif; ?>
									      	</td>
									      <?php endif; ?>
									      <?php if ($this->params->get('list_show_hits', 1)) : ?>
									      	<td headers="categorylist_header_hits" class="list-hits">
									      		<span class="badge badge-info">
									      			<?php echo JText::sprintf('JGLOBAL_HITS_COUNT', $article->hits); ?>
									      		</span>
									      	</td>
									      <?php endif; ?>
										      <?php if ($list_show_servicerequest_item) :?>
									      	<td headers="categorylist_header_servicerequest_item" class="list-servicerequest-item">
									      		<?php echo $attribs->get('service_name'); ?>
									      	</td>
									      <?php endif; ?>
									      <?php if ($list_show_servicerequest_approval) :?>
									      <td headers="categorylist_header_servicerequest_approval" class="list-servicerequest-approval">
									      		<?php
									      			$servicerequest_approval = $attribs->get('servicerequest_approval');
									      			switch ($servicerequest_approval) {
									      				case '0':
									      					echo "Pending";
									      					break;
									      				case '1':
									      					echo "Approved";
									      					break;
									      				case '2':
									      					echo "Denied";
									      					break;
									      				default:
									      					break;
									      			};
									      		?>
									      </td>
									      <?php endif; ?>
										      <?php if ($list_show_billing_name) : ?>
										      <td headers="categorylist_header_billing_name" class="list-billing-name">
										      		<?php echo $attribs->get('billing_tenant_name'); ?>
										      </td>
									      <?php endif; ?>
									      <?php if ($list_show_billing_price) : ?>
							    			<td headers="categorylist_header_billing_price" class="list-billing-price">
							      			<?php echo $attribs->get('billing_amount'); ?>
							      			</td>
										  <?php endif; ?>
									      <?php if ($list_show_billing_status) : ?>
										      <td headers="categorylist_header_billing_status" class="list-billing-status">
										      		<?php 
										      			$billing_status = $attribs->get('billing_status'); 
										      			switch ($billing_status) {
										      				case '0':
										      					echo "Unpaid";
										      					break;
										      				case '1':
										      					echo "Paid";
										      					break;
										      				default:
										      					break;
										      			};
										      		?>
										      </td>
									      <?php endif; ?>
										      <?php if ($list_show_category_title) : ?>
										      <td headers="categorylist_header_category_title" class="list-category-title">
										      		<?php echo $article->category_title; ?>
										      </td>
									      <?php endif; ?>

									      <?php if ($isEditable) : ?>
									      	<td headers="categorylist_header_edit" class="list-edit">
									      		<?php if ($article->params->get('access-edit')) : ?>
									      			<?php echo JHtml::_('icon.edit', $article, $params); ?>
									      		<?php endif; ?>
									      	</td>
									      <?php endif; ?>
									  </tr>
									<?php endif; ?>

								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

				<?php // Code to add a link to submit an article. ?>
				<?php if ($this->category->getParams()->get('access-create')) : ?>
					<?php echo JHtml::_('icon.create', $this->category, $this->category->params); ?>
				<?php  endif; ?>

				<?php // Add pagination links ?>
				<?php if (!empty($this->items)) : ?>
					<?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
						<br/>
						<div class="pagination">

							<?php if ($this->params->def('show_pagination_results', 1)) : ?>
								<p class="counter pull-right">
									<?php echo $this->pagination->getPagesCounter(); ?>
								</p>
							<?php endif; ?>

							<?php echo $this->pagination->getPagesLinks(); ?>
						</div>
					<?php endif; ?>
				</form>
			<?php  endif; ?>