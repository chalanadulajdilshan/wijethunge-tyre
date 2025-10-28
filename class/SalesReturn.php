<?php

class SalesReturn
{
    public $id;
    public $return_no;
    public $return_date;
    public $invoice_no;
    public $customer_id;
    public $total_amount;
    public $return_reason;
    public $remarks;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `sales_return` WHERE `id` = " . (int)$id;
            $db = new Database();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->return_no = $result['return_no'];
                $this->return_date = $result['return_date'];
                $this->invoice_no = $result['invoice_no'];
                $this->customer_id = $result['customer_id'];
                $this->total_amount = $result['total_amount'];
                $this->return_reason = $result['return_reason'];
                $this->remarks = $result['remarks'];
                $this->created_by = $result['created_by'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `sales_return` (
            `return_no`, `return_date`, `invoice_no`, `customer_id`, `total_amount`, `return_reason`, `remarks`, `created_by`, `created_at`, `updated_at`
        ) VALUES (
            '$this->return_no', '$this->return_date', '$this->invoice_no', '$this->customer_id', '$this->total_amount', '$this->return_reason', '$this->remarks', '$this->created_by', NOW(), NOW()
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
        $query = "UPDATE `sales_return` SET
            `return_no` = '$this->return_no',
            `return_date` = '$this->return_date',
            `invoice_no` = '$this->invoice_no',
            `customer_id` = '$this->customer_id',
            `total_amount` = '$this->total_amount',
            `return_reason` = '$this->return_reason',
            `remarks` = '$this->remarks',
            `created_by` = '$this->created_by',
            `updated_at` = NOW()
            WHERE `id` = '$this->id'";

        $db = new Database();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `sales_return` WHERE `id` = '$this->id'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `sales_return` ORDER BY `created_at` DESC";
        $db = new Database();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getByCustomerId($customer_id)
    {
        $query = "SELECT * FROM `sales_return` WHERE `customer_id` = '$customer_id' ORDER BY `created_at` DESC";
        $db = new Database();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getLastID()
    {
        $query = "SELECT `id` FROM `sales_return` ORDER BY `id` DESC LIMIT 1";
        $db = new Database();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? null;
    }

    public function checkReturnNoExist($return_no, $exclude_id = null)
    {
        $query = "SELECT COUNT(*) as count FROM `sales_return` WHERE `return_no` = '$return_no'";
        if ($exclude_id) {
            $query .= " AND `id` != '$exclude_id'";
        }
        $db = new Database();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['count'] > 0;
    }
}

?>
