<?php
class SalesOrder {
    public $id;
    public $sales_order_id;
    public $payment_type;
    public $department_id;
    public $order_date;
    public $customer_id;
    public $invoice_type;
    public $sales_executive_id;
    public $status;

    public function __construct($id = NULL) {
        if ($id) {
            $query = "SELECT * FROM sales_order WHERE id = '$id'";
            $db = new Database();
            $result = $db->readQuery($query);
            $data = mysqli_fetch_assoc($result);
            if ($data) {
                $this->id = $data['id'];
                $this->sales_order_id = $data['sales_order_id'];
                $this->payment_type = $data['payment_type'];
                $this->department_id = $data['department_id'];
                $this->order_date = $data['order_date'];
                $this->customer_id = $data['customer_id'];
                $this->invoice_type = $data['order_type'] ?? null; // Keep reading from 'order_type' column for now
                $this->sales_executive_id = $data['sales_executive_id'];
                $this->status = $data['status'];
            }
        }
    }

    public function create() {
        $query = "INSERT INTO sales_order 
                    (sales_order_id, payment_type, department_id, order_date, customer_id, order_type, sales_executive_id, status)
                  VALUES (
                    '{$this->sales_order_id}', 
                    '{$this->payment_type}', 
                    '{$this->department_id}', 
                    '{$this->order_date}', 
                    '{$this->customer_id}', 
                    '{$this->invoice_type}',
                    '{$this->sales_executive_id}',
                    '0'
                  )";
        $db = new Database();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE sales_order SET
                    sales_order_id = '{$this->sales_order_id}',
                    payment_type = '{$this->payment_type}',
                    department_id = '{$this->department_id}',
                    order_date = '{$this->order_date}',
                    customer_id = '{$this->customer_id}',
                    order_type = '{$this->invoice_type}',
                    sales_executive_id = '{$this->sales_executive_id}',
                    status = '{$this->status}'
                  WHERE id = '{$this->id}'";
        $db = new Database();
        $result = $db->readQuery($query);
        if ($result) {
            return $this->__construct($this->id);
        } else {
            return FALSE;
        }
    }

    public function markAsInvoiced() {
        $query = "UPDATE sales_order SET status = 1 WHERE id = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function markAsInvoiceCancelled() {
        $query = "UPDATE sales_order SET status = 2 WHERE id = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function all() {
        $query = "SELECT * FROM sales_order WHERE status = 0 ORDER BY id DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array[] = $row;
        }
        return $array;
    }
}
?>