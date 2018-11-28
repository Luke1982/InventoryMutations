#InventoryMutations

InventoryMutations is a very simple, yet incredibly powerful module that will allow you to take control of you stock the way you want to.

## What does it do?
It has some simple fields:
- Quantity before
- Quantity after
- Quantity mutated
- Units delivered received before
- Units delivered received after
- Units delivered received mutated
- Inventorydetails line. For which inventorydetails line is this mutation?
- Product. For which product was this mutation?
- Source record. Could be a SalesOrder, Invoice or PurchaseOrder.

It installs a related list in InventoryDetails where it lists its records when related to this InventoryDetails record. But the real magic is that *every* time an InventoryDetails record is saved and either the quantity or the units delivered / received field change an InventoryMutations record is created.

## What will it allow you to do?
Since this is an 'entity module', you can use the workflow system to listen to the creation of the InventoryMutation records. You could create a workflow that:
- Only fires when an InventoryMutations record is created.
- Checks which module is the source (you could for instance say "Is the PurchaseOrder no. 'not empty'"?)
- Then perfom simple tasks to the related product like:
-- Take the Qty in stock field of the product and increase it by itself + the quantity mutated on units delivered / received.

That way, you can use the 'units delivered / received' field in PurchaseOrder lines to update your stock exactly how you want it.