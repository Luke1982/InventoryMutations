<?php
/*************************************************************************************************
 * Copyright 2018 MajorLabel -- This file is a part of MajorLabel coreBOS Customizations.
* Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
* file except in compliance with the License. You can redistribute it and/or modify it
* under the terms of the License. MajorLabel reserves all rights not expressly
* granted by the License. coreBOS distributed by MajorLabel is distributed in
* the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
* warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
* applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
* either express or implied. See the License for the specific language governing
* permissions and limitations under the License. You may obtain a copy of the License
* at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
*************************************************************************************************/
Class CreateInventoryMutation extends VTEventHandler {
	public function handleEvent($eventName, $entityData){
		global $current_user, $adb;

		$moduleName = $entityData->getModuleName();
		if ($moduleName == 'InventoryDetails') {

			require_once 'modules/InventoryMutations/InventoryMutations.php';
			require_once 'data/VTEntityDelta.php';

			$delta = VTEntityDelta::getEntityDelta('InventoryDetails', $entityData->getId());
			$data = $entityData->getData();
			$related_item = getSalesEntityType($data['productid']);
			$deltas = array();

			if ($related_item == 'Products') {

				if (array_key_exists('quantity', $delta)){ 
					$deltas['quantity_before'] = $delta['quantity']['oldValue'];
					$deltas['quantity_after'] = $delta['quantity']['currentValue'];
					$deltas['quantity_mutated'] = (float)$delta['quantity']['currentValue'] - (float)$delta['quantity']['oldValue'];
				}
				if (array_key_exists('units_delivered_received', $delta)){ 
					$deltas['units_delrec_before'] = $delta['units_delivered_received']['oldValue'];
					$deltas['units_delrec_after'] = $delta['units_delivered_received']['currentValue'];
					$deltas['units_delrec_mutated'] = (float)$delta['units_delivered_received']['currentValue'] - (float)$delta['units_delivered_received']['oldValue'];
				}

				if (count($deltas) > 0) {
					$im = new InventoryMutations();
					$im->mode = 'create';

					$im->column_fields = $deltas;
					$im->column_fields['invmut_inventorydetails_id'] = $entityData->getId();
					$im->column_fields['invmut_source_id'] = $data['related_to'];
					$im->column_fields['invmut_product_id'] = $data['productid'];

					$handler = vtws_getModuleHandlerFromName('InventoryMutations', $current_user);
					$meta = $handler->getMeta();
					$im->column_fields = DataTransform::sanitizeRetrieveEntityInfo($im->column_fields, $meta);
					$im->saveentity('InventoryMutations');
				}
			}
		
		}
	}
}