<?php

class SalesInvoiceItem
{
    public $id;
    public $invoice_id;
    public $item_code;
    public $service_item_code;
    public $item_name;
    public $quantity;
    public $cost;
    public $price;
    public $customer_price;
    public $dealer_price;
    public $discount;
    public $total;
    public $vehicle_no;
    public $current_km;
    public $next_service_date;
    public $created_at;
    public $sales_order_id;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT  * 
                      FROM `sales_invoice_items` 
                      WHERE `id` = " . (int) $id;
            $db = new Database();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->invoice_id = $result['invoice_id'];
                $this->item_code = $result['item_code'];
                $this->service_item_code = $result['service_item_code'];
                $this->item_name = $result['item_name'];
                $this->quantity = $result['quantity'];
                $this->discount = $result['discount'];
                $this->cost = $result['cost'];
                $this->price = $result['price'] ?? 0;
                $this->customer_price = $result['customer_price'] ?? $result['list_price']; // Fallback for existing records
                $this->dealer_price = $result['dealer_price'] ?? $result['price']; // Fallback for existing records
                $this->total = $result['total'];
                $this->vehicle_no = $result['vehicle_no'] ?? '';
                $this->current_km = $result['current_km'] ?? '';
                $this->next_service_date = $result['next_service_date'] ?? '';
                $this->created_at = $result['created_at'];
                $this->sales_order_id = $result['sales_order_id'] ?? null;
            }
        }
    }

    public function create()
    {


        $query = "INSERT INTO `sales_invoice_items` 
    (`invoice_id`, `item_code`, `service_item_code`, `item_name`,`cost`, `price`, `customer_price`, `dealer_price`, `discount`,`quantity`, `total`, `vehicle_no`, `current_km`, `next_service_date`, `sales_order_id`, `created_at`) 
    VALUES (
        '{$this->invoice_id}', 
        '{$this->item_code}', 
        '{$this->service_item_code}', 
        '{$this->item_name}', 
        '{$this->cost}', 
        '{$this->price}', 
        '{$this->customer_price}', 
        '{$this->dealer_price}', 
        '{$this->discount}', 
        '{$this->quantity}', 
        '{$this->total}',
        '{$this->vehicle_no}',
        '{$this->current_km}',
        '{$this->next_service_date}',
        " . ($this->sales_order_id ? "'{$this->sales_order_id}'" : "NULL") . ",
        NOW()
    )";



        $db = new Database();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update()
    {
        $query = "UPDATE `sales_invoice_items` SET 
             
            `item_code` = '{$this->item_code}', 
            `service_item_code` = '{$this->service_item_code}', 
            `item_name` = '{$this->item_name}', 
            `price` = '{$this->price}', 
            `dealer_price` = '{$this->dealer_price}', 
            `quantity` = '{$this->quantity}', 
            `total` = '{$this->total}' 
            WHERE `id` = '{$this->id}'";

        $db = new Database();
        $result = $db->readQuery($query);

        if ($result) {
            return $this->__construct($this->id);
        } else {
            return false;
        }
    }

    public function delete()
    {
        $query = "DELETE FROM `sales_invoice_items` WHERE `id` = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function getByInvoiceId($invoice_id)
    {
        $query = "SELECT * FROM `sales_invoice_items` WHERE `invoice_id` = '{$invoice_id}' ORDER BY `id` ASC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = [];

        if ($result) {
            while ($row = mysqli_fetch_array($result)) {
                array_push($array_res, $row);
            }
        }

        return $array_res;
    }

    public function all()
    {
        $query = "SELECT  * 
                  FROM `sales_invoice_items` 
                  ORDER BY `id` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getItemsByInvoiceId($invoice_id)
    {
        $query = "SELECT * 
                  FROM `sales_invoice_items` 
                  WHERE `invoice_id` = $invoice_id 
                  ORDER BY `id` DESC";
    
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = array();
    
        while ($row = mysqli_fetch_assoc($result)) {
            // safely load item master
            if ($row['item_code'] !=0) {
                $item_master = new ItemMaster($row['item_code']);
                $row['item_code_name'] = $item_master->code ?? '';
            } else {
                 $service_item_master = new ServiceItem($row['service_item_code']);
                 $row['item_code_name'] = $service_item_master->item_code ?? '';
            }
            
            // Extract clean item name for display (remove ARN metadata)
            $row['display_name'] = $this->extractCleanItemName($row['item_name']);
            
            // Ensure price field is available with fallback
            if (!isset($row['price']) || $row['price'] === null) {
                $row['price'] = $row['customer_price'] ?? $row['dealer_price'] ?? 0;
            }
            
            // Add vehicle no and current km to display name if they exist
            if (!empty($row['vehicle_no']) || !empty($row['current_km'])) {
                $vehicleInfo = ' [' . ($row['vehicle_no'] ?: 'N/A') . ' - ' . ($row['current_km'] ?: 'N/A') . ' KM]';
                $row['display_name'] .= $vehicleInfo;
            }
    
            $array_res[] = $row; // push AFTER adding new field
        }
    
        return $array_res;
    }
    
    // Helper method to extract clean item name without ARN metadata
    private function extractCleanItemName($itemName)
    {
        if (strpos($itemName, '|ARN:') !== false) {
            $parts = explode('|ARN:', $itemName);
            return trim($parts[0]);
        }
        return $itemName;
    }
    



}
