<?php
if (!defined('_CAN_LOAD_FILES_'))
	exit;

class RedirectDeletedProducts extends Module {
	private $_input_errors;

	public function __construct() {
		$this->name = 'redirectdeletedproducts';
		$this->tab = 'others';
		$this->need_instance = 1;
		
		parent::__construct();
		
		$this->displayName = $this->l('301-Redirect Products');
		$this->description = $this->l("This module will keep a log of deleted and deactivated products. When a product is deleted/deactivated, it's category and URL will be recorded and stored. If the URL is accessed, the user will be 301-redirected to the category page.");
		$this->confirmUninstall = $this->l("Are you sure you want to delete this module? The current log will also be deleted, and cannot be restored!");
		
		$this->version = '1.0';
		$this->author = "1337 ApS";
		$this->error = false;
		$this->valid = false;		
	}
	
	public function getContent(){
		$this->_html = '<h2>'.$this->displayName.'</h2>';
		$this->_postProcess();
		$this->_displayForm();
		return $this->_html;
		return $this->_html;
	}
	
	private function _postProcess(){
		if(isset($_GET['r_edit']) && is_numeric($_GET['r_edit'])){
			$id = $_GET['r_edit'];
			
			$sql = "SELECT * FROM `". _DB_PREFIX_ ."redirdelprod_log` WHERE `id_product` = ".((int)$id);
			$this->_edit = Db::getInstance()->getRow($sql);
		}else{
			$this->_edit = array('id_product' => '', 'name' => '', 'id_category' => 0, 'redirect_link' => '', 'redirect_type' => '301');
		}
		
		if(isset($_GET['r_delete']) && is_numeric($_GET['r_delete'])){
			$id = (int)$_GET['r_delete'];
			
			Db::getInstance()->delete(_DB_PREFIX_ ."redirdelprod_log", '`id_product` = '.$id);
		}
		
		if(isset($_POST['r_action'])){
			$action = ($_POST['r_action'] == 'edit' ? 'UPDATE' : 'INSERT');
			$id_product = (int) $_POST['r_id_product'];
			$name = pSQL($_POST['r_name']);
			$id_category = (int) $_POST['r_id_category'];
			$redirect_link = pSQL($_POST['r_redirect_link']);
			$redirect_type = (int) $_POST['r_redirect_type'];
			
			$err = array();
			if(!is_numeric($id_product) || $id_product < 1)
				$err[] = $this->l('The product ID should be an integer larger than 0.');
			if(!is_numeric($id_category) || $id_category < 0)
				$err[] = $this->l('The category ID should be an integer larger than or equal to 0.');
			if(empty($name))
				$err[] = $this->l('Please input a name for the redirect.');
			if(empty($id_category) && empty($redirect_link))
				$err[] = $this->l('You need to specify either a category or link as destination of the redirect.');
			
			// Set default redirect type
			if($redirect_type != '301' && $redirect_type != '302')
				$redirect_type = '301';
			
			if(empty($err))
				Db::getInstance()->autoExecute(_DB_PREFIX_ ."redirdelprod_log", array('id_product' => $id_product, 'name' => $name, 'id_category' => $id_category, 'redirect_link' => $redirect_link, 'redirect_type' => $redirect_type), $action);
			else{
				$this->_input_errors = $err;
				$this->_edit = array('id_product' => ($id_product == 0 ? '' : $id_product), 'name' => $_POST['r_name'], 'id_category' => $id_category, 'redirect_link' => $_POST['r_redirect_link']);
			}
		}
	}
	
	private function _displayForm(){
		global $cookie, $currentIndex;
		$token = Tools::getValue('token');
		$url = "$currentIndex&token=$token&configure=redirectdeletedproducts";
		
		$sql = "SELECT * FROM `". _DB_PREFIX_ ."redirdelprod_log` ORDER BY `id_product` ASC";
		$redirects = Db::getInstance()->ExecuteS($sql);

		$this->_html .= '
			<fieldset class="width4"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Current redirects').'</legend>
				<table style="width: 100%;">
					<thead>
						<tr>
							<th style="min-width: 30px;">'.$this->l('ID').'</th>
							<th>'.$this->l('Name').'</th>
							<th>'.$this->l('Category').'</th>
							<th>'.$this->l('Link').'</th>
							<th>'.$this->l('Type').'</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>';
		
					foreach($redirects as $r){
						if($r['id_category'] > 0){
							$c = new Category($r['id_category'], $cookie->id_lang);
							$cat_name = $c->name;
						}else
							$cat_name = '-';
						
						$this->_html .= "
							<tr>
								<td>$r[id_product]</td>
								<td>$r[name]</td>
								<td>$cat_name</td>
								<td>".(empty($r['redirect_link']) ? '-' : $r['redirect_link'])."</td>
								<td>$r[redirect_type]</td>
								<td style='width: 40px; text-align: right;'><a href='$url&r_edit=$r[id_product]'><img src='".__PS_BASE_URI__."img/admin/edit.gif' width='16' height='16' /></a><a href='$url&r_delete=$r[id_product]'><img src='".__PS_BASE_URI__."img/admin/delete.gif' width='16' height='16' /></a></td>
							</tr>";
					}
			$this->_html .= '</tbody>
				</table>
			</fieldset>
			<br />
			<form action="'.$url.'" method="post">
				<input type="hidden" name="r_action" value="'.($this->_edit['id_product'] > 0 ? 'edit' : 'add').'"
				<fieldset class="width3"><legend><img src="'.$this->_path.'logo.gif" /> '.$this->l('Add Redirect').'</legend>
					<label>'.$this->l('Product ID:').' </label>
					<div class="margin-form">
						<input type="text" value="'.$this->_edit['id_product'].'" id="r_id_product" name="r_id_product" />
						&nbsp;<label for="r_id_product" class="t">'.$this->l('This can be either an existing/deactivated or a previously existing product ID.').'</label>
					</div>
					
					<label>'.$this->l('Name:').' </label>
					<div class="margin-form">
						<input type="text" value="'.$this->_edit['name'].'" id="r_name" name="r_name" />
						&nbsp;<label for="r_name" class="t">'.$this->l('Name for easy identification.').'</label>
					</div>
					
					<label>'.$this->l('Category:').' </label>
					<div class="margin-form">
						<select name="r_id_category" id="r_id_category">
							<option value="0">'.$this->l('--- No category ---').'</option>';
					
							$categories = Category::getSimpleCategories($cookie->id_lang);
							foreach($categories as $c)
								if($c['id_category'] > 1)
									$this->_html .= "<option value='$c[id_category]'".($c['id_category'] == $this->_edit['id_category'] ? ' selected' : '').">$c[name]</option>";
							

		$this->_html .= '</select>
						&nbsp;<label for="r_id_category" class="t">'.$this->l('Category that should be redirected to. If no category is specified, remember to input a redirect link.').'</label>
					</div>
					
					<label>'.$this->l('Redirect link:').' </label>
					<div class="margin-form">
						<input type="text" value="'.$this->_edit['redirect_link'].'" id="r_redirect_link" name="r_redirect_link" />
						&nbsp;<label for="r_redirect_link" class="t">'.$this->l('Alternative redirect link to be used instead of category. This has higher priority than category, and will always be used if there\' any value here.').'</label>
					</div>
					

					<label>'.$this->l('Redirect type:').' </label>
					<div class="margin-form">
						<select name="r_redirect_type" id="r_redirect_type">
							<option value="301"'.($this->_edit['redirect_type'] == '301' ? ' selected' : '').'>'.$this->l('301 - Moved Permanently').'</option>
							<option value="302"'.($this->_edit['redirect_type'] == '302' ? ' selected' : '').'>'.$this->l('302 - Moved Temporarily').'</option>
						</select>
						&nbsp;<label for="r_redirect_type" class="t">'.$this->l('Choose what kind of redirect you want to do. 301 (Moved Permanently) is default.').'</label>
					</div>';
		
		if(count($this->_input_errors) > 0){
			$this->_html .= '<label>'.$this->l('Input errors:').' </label>
					<div class="margin-form">
						<ul><li>';
						$this->_html .= implode('</li><li>', $this->_input_errors);
			$this->_html .= '</li></ul>
					</div>';
		}
					
		$this->_html .= '<div class="margin-form">
						<input type="submit" value="'.$this->l('   Save   ').'" name="submitMACustomer" class="button" />
					</div>
				</fieldset>
			</form>';
	}
	
	public function hookActionProductSave( $params ){
		$id_product = (int)$params['id_product'];
		
		if(empty($id_product))
			return;
		
		$product = new Product( $id_product );
		
		if($product->active)
			$this->removeFromLog( $product );
		else
			$this->addToLog( $product );
		
	}
	
	public function hookActionProductDelete($params){
		$product = $params['product'];
		$this->addToLog( $product );
	}
	
	private function addToLog( $id_product, $id_lang = null ){
		if(is_object($id_product)){
			$product = $id_product;
			$id_product = (int)$product->id;
		}else{
			$product = new Product( $id_product );
		}
		
		if($id_lang == null)
			$id_lang = (int)$this->context->language->id;
		
		$name = pSQL($product->name[$id_lang]);
		$category = (int)$product->id_category_default;
		
		// Check if already exists
		$exists = Db::getInstance()->getValue( "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "redirdelprod_log` WHERE `id_product` = " . $id_product );
		
		if($exists)
			return;
		
		if($id_product > 0 && !empty($name) && $category > 0)	
			Db::getInstance()->autoExecute(_DB_PREFIX_ ."redirdelprod_log", array('id_product' => $id_product, 'name' => $name, 'id_category' => $category, 'redirect_link' => ''), 'INSERT');
	}
	
	private function removeFromLog( $id_product ){
		if(is_object($id_product))
			$id_product = (int)$id_product->id;
		
		// Check if already exists
		$exists = Db::getInstance()->getValue( "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "redirdelprod_log` WHERE `id_product` = " . $id_product );
		
		if(!$exists)
			return;
		
		Db::getInstance()->delete(_DB_PREFIX_ ."redirdelprod_log", '`id_product` = ' . $id_product);
	}
	
	public function hookDisplayHeader($params){
		// Check if we should do anything
		if(!isset($_GET['id_product']))
			return;
		
		$db = Db::getInstance();
		
		$sql = "SELECT * FROM `". _DB_PREFIX_ ."redirdelprod_log` WHERE `id_product` = " . pSQL($_GET['id_product']);
		$data = $db->getRow($sql);
		
		// Check if we should do any redirects
		if(empty($data))
			return;
		
		if(!empty($data['redirect_link']))
			$redirect = $data['redirect_link'];
		elseif(!empty($data['id_category'])){
			global $link;
			global $cookie;
			
			$category = new Category($data['id_category'], $cookie->id_lang);
			$redirect = $link->getCategoryLink($data['id_category'], $category->link_rewrite, $cookie->id_lang);
		}else
			$redirect = __PS_BASE_URI__;

		// Set header according to redirect type
		if($data['redirect_type'] == '302'){
			header("Status: 302 Moved Temporarily"); // for fast cgi
			header($_SERVER['SERVER_PROTOCOL'] . " 302 Moved Temporarily");
		}else{
			header("Status: 301 Moved Permanently"); // for fast cgi
			header($_SERVER['SERVER_PROTOCOL'] . " 301 Moved Permanently");
		}

		Tools::redirectLink($redirect);
	}

	public function install(){
		if(!parent::install() || !$this->registerHook('actionProductDelete') || !$this->registerHook('displayHeader') || !$this->registerHook('actionProductSave') || !$this->installDB())
			return false;
		
		return true;
	}
	
	public function uninstall(){
		if(!parent::uninstall())
			return false;
		
		$this->uninstallDB();
		
		return true;
	}
	
	private function installDB(){
		$db = Db::getInstance();
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "redirdelprod_log` (
					`id_product` INT(10) UNSIGNED NOT NULL,
					`name` VARCHAR(128) NOT NULL,
					`id_category` INT(10) UNSIGNED NOT NULL,
					`redirect_link` VARCHAR(255) NOT NULL DEFAULT '',
					`redirect_type` ENUM('301', '302') DEFAULT '301',
					PRIMARY KEY(`id_product`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		
		$db->execute($sql);
		
		return true;
	}
	
	private function uninstallDB(){
		$db = Db::getInstance();
		
		$db->Execute("DROP TABLE `". _DB_PREFIX_ ."redirdelprod_log`");
	}
}