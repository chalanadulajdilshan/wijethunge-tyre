<?php

class SalesExecutiveOutstanding
{
    public $id;
    public $sale_ex_id;
    public $invoice_id;
    public $customer_id;
    public $amount;
    public $created_at;

    // Constructor – Load data if ID provided
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sales_executive_outstanding` WHERE `id` = " . (int)$id;
            $db = new Database();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->sale_ex_id = $result['sale_ex_id'];
                $this->invoice_id = $result['invoice_id'];
                $this->customer_id = $result['customer_id'];
                $this->amount = $result['amount'];
                $this->created_at = $result['created_at'];
            }
        }
    }

    // Create a new outstanding record
    public function create()
    {
        $query = "INSERT INTO `sales_executive_outstanding` (`sale_ex_id`, `invoice_id`, `customer_id`, `amount`)
                  VALUES ('" . $this->sale_ex_id . "', '" . $this->invoice_id . "', '" . $this->customer_id . "', '" . $this->amount . "')";

        $db = new Database();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON); // Return new ID
        } else {
            return false;
        }
    }

    // Update an existing outstanding record
    public function update()
    {
        $query = "UPDATE `sales_executive_outstanding` SET 
                    `sale_ex_id` = '" . $this->sale_ex_id . "',
                    `invoice_id` = '" . $this->invoice_id . "',
                    `customer_id` = '" . $this->customer_id . "',
                    `amount` = '" . $this->amount . "'
                  WHERE `id` = '" . $this->id . "'";

        $db = new Database();
        $result = $db->readQuery($query);

        return $result ? true : false;
    }

    // Delete record by ID
    public function delete()
    {
        $query = "DELETE FROM `sales_executive_outstanding` WHERE `id` = '" . $this->id . "'";
        $db = new Database();
        return $db->readQuery($query);
    }

    // Get all outstanding records
    public function all()
    {
        $query = "SELECT * FROM `sales_executive_outstanding` ORDER BY `id` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get all records by Sales Executive ID
    public function getBySalesExecutiveId($sale_ex_id)
    {
        $query = "SELECT * FROM `sales_executive_outstanding` WHERE `sale_ex_id` = '" . (int)$sale_ex_id . "' ORDER BY `id` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get all records by Customer ID
    public function getByCustomerId($customer_id)
    {
        $query = "SELECT * FROM `sales_executive_outstanding` WHERE `customer_id` = '" . (int)$customer_id . "' ORDER BY `id` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Get total outstanding amount for a specific sales executive
    public function getTotalBySalesExecutive($sale_ex_id)
    {
        $query = "SELECT SUM(`amount`) AS total_outstanding 
                  FROM `sales_executive_outstanding`
                  WHERE `sale_ex_id` = '" . (int)$sale_ex_id . "'";
        $db = new Database();
        $result = mysqli_fetch_array($db->readQuery($query));

        return $result ? $result['total_outstanding'] : 0;
    }

    public function updateMarketingExecutiveOutstanding($sale_ex_id, $invoice_id, $amount)
    {
        $query = "UPDATE `sales_executive_outstanding` SET 
                    `amount` = '" . $amount . "'
                  WHERE `sale_ex_id` = '" . (int)$sale_ex_id . "' AND invoice_id = '" . (int)$invoice_id . "'";
        $db = new Database();
        $result = $db->readQuery($query);

        return $result ? true : false;
    }
}
?>
