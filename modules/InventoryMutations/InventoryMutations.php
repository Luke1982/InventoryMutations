<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class InventoryMutations extends CRMEntity {
	public $db;
	public $log;

	public $table_name = 'vtiger_inventorymutations';
	public $table_index= 'inventorymutationsid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_inventorymutationscf', 'inventorymutationsid');
	// related_tables variable should define the association (relation) between dependent tables
	// FORMAT: related_tablename => array(related_tablename_column[, base_tablename, base_tablename_column[, related_module]] )
	// Here base_tablename_column should establish relation with related_tablename_column
	// NOTE: If base_tablename and base_tablename_column are not specified, it will default to modules (table_name, related_tablename_column)
	// Uncomment the line below to support custom field columns on related lists
	// var $related_tables = array('vtiger_inventorymutationscf' => array('inventorymutationsid', 'vtiger_inventorymutations', 'inventorymutationsid', 'inventorymutations'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_inventorymutations', 'vtiger_inventorymutationscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_inventorymutations'   => 'inventorymutationsid',
		'vtiger_inventorymutationscf' => 'inventorymutationsid',
	);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'inventorymutations_no'=> array('inventorymutations' => 'inventorymutations'),
		'quantity_before'=> array('inventorymutations' => 'quantity_before'),
		'quantity_after'=> array('inventorymutations' => 'quantity_after'),
		'quantity_mutated'=> array('inventorymutations' => 'quantity_mutated'),
		'units_delrec_before'=> array('inventorymutations' => 'units_delrec_before'),
		'units_delrec_after'=> array('inventorymutations' => 'units_delrec_after'),
		'units_delrec_mutated'=> array('inventorymutations' => 'units_delrec_mutated'),
		'Assigned To' => array('crmentity' => 'smownerid'),
	);
	public $list_fields_name = array(
		/* Format: Field Label => fieldname */
		'inventorymutations_no'=> 'inventorymutations_no',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	public $list_link_field = 'inventorymutations_no';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'inventorymutations_no'=> array('inventorymutations' => 'inventorymutations_no')
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'inventorymutations_no'=> 'inventorymutations_no'
	);

	// For Popup window record selection
	public $popup_fields = array('inventorymutations_no');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'inventorymutations_no';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'inventorymutations_no';

	// Required Information for enabling Import feature
	public $required_fields = array('inventorymutations_no'=>1);

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'inventorymutations_no';
	public $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('createdtime', 'modifiedtime', 'inventorymutations_no');

	public function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$this->setModuleSeqNumber('configure', $modulename, '', '7010000001');

			include_once('vtlib/Vtiger/Module.php');
			$invdet = Vtiger_Module::getInstance('InventoryDetails');
			$invmut = Vtiger_Module::getInstance('InventoryMutations');
			$invdet->setRelatedList($invmut, 'LBL_RELATED_INV_MUT', array(), 'get_dependents_list');

			$this->updateLangFor('InventoryDetails', $this->i18n_invdet);
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			global $adb;
			$adb->query("DROP TABLE vtiger_inventorymutations");
			$adb->query("DROP TABLE vtiger_inventorymutationscf");			
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// public function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	private $i18n_invdet = array(
		'langs' => array('en_us', 'nl_nl'),
		'LBL_RELATED_INV_MUT' => array(
			'en_us' => 'Related Mutations',
			'nl_nl' => 'Gerelateerde mutaties',
		),
	);

	private function updateLangFor($modulename, $i18n) {
		$langs = $i18n['langs'];
		unset($i18n['langs']);
		foreach ($langs as $lang) {
			$lang_file = 'modules/' . $modulename . '/language/' . $lang . '.custom.php';
			if (file_exists($lang_file)) {
				include $lang_file;
			} else {
				$custom_strings = array();
			}
			foreach ($i18n as $label => $langs) {
				foreach ($langs as $lang => $value) {
					if (strpos($lang_file, $lang) !== false) {
						// Lang exists and we have a translation for it
						if (!array_key_exists($label, $custom_strings)) {
							// We don't have this label yet
							$custom_strings[$label] = $value;
						}
						file_put_contents($lang_file, "<?php\n\$custom_strings = " . var_export($custom_strings, true) . ";");
					}
				}
			}
		}
	}	
}
?>
