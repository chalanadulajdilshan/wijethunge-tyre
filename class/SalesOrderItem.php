<?php
class SalesOrderItem {
    public $id;
    public $sales_order_id;
    public $item_id;
    public $qty;

    public function __construct($id = NULL) {
        if ($id) {
            $query = "SELECT * FROM sales_order_items WHERE id = '$id'";
            $db = new Database();
            $result = $db->readQuery($query);
            $data = mysqli_fetch_assoc($result);
            if ($data) {
                $this->id = $data['id'];
                $this->sales_order_id = $data['sales_order_id'];
                $this->item_id = $data['item_id'];
                $this->qty = $data['qty'];
            }
        }
    }

    public function create() {
        $query = "INSERT INTO sales_order_items (sales_order_id, item_id, qty)
                  VALUES ('{$this->sales_order_id}', '{$this->item_id}', '{$this->qty}')";
                  
        $db = new Database();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE sales_order_items SET
                    sales_order_id = '{$this->sales_order_id}',
                    item_id = '{$this->item_id}',
                    qty = '{$this->qty}'
                  WHERE id = '{$this->id}'";
        $db = new Database();
        $result = $db->readQuery($query);
        if ($result) {
            return $this->__construct($this->id);
        } else {
            return FALSE;
        }
    }

    public function delete() {
        $query = "DELETE FROM sales_order_items WHERE id = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function all() {
        $query = "SELECT * FROM sales_order_items ORDER BY id DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $array[] = $row;
        }
        return $array;
    }

    public static function getByOrderId($sales_order_id) {
        $query = "SELECT * FROM sales_order_items WHERE sales_order_id = '$sales_order_id'";
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