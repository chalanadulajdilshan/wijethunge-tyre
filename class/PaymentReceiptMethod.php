<?php

class PaymentReceiptMethod
{
    public $id;
    public $receipt_id;
    public $invoice_id;
    public $payment_type_id;
    public $amount;
    public $cheq_no;
    public $bank_id;
    public $branch_id;
    public $cheq_date;
    public $is_settle;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                             `cheq_no`, `bank_id`, `branch_id`, `cheq_date`
                      FROM `payment_receipt_method`
                      WHERE `id` = " . (int)$id;

            $db = new Database();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->receipt_id = $result['receipt_id'];
                $this->invoice_id = $result['invoice_id'];
                $this->payment_type_id = $result['payment_type_id'];
                $this->amount = $result['amount'];
                $this->cheq_no = $result['cheq_no'];
                $this->bank_id = $result['bank_id'];
                $this->branch_id = $result['branch_id'];
                $this->cheq_date = $result['cheq_date'];
                $this->is_settle = $result['is_settle'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `payment_receipt_method` 
                    (`receipt_id`, `invoice_id`, `payment_type_id`, `amount`, `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`) 
                  VALUES (
                    '{$this->receipt_id}',
                    '{$this->invoice_id}',
                    '{$this->payment_type_id}',
                    '{$this->amount}',
                    '{$this->cheq_no}',
                    '{$this->bank_id}',
                    '{$this->branch_id}',
                    '{$this->cheq_date}',
                    '{$this->is_settle}'
                  )";

        $db = new Database();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        $query = "UPDATE `payment_receipt_method`
                  SET 
                    `receipt_id` = '{$this->receipt_id}',
                    `invoice_id` = '{$this->invoice_id}',
                    `payment_type_id` = '{$this->payment_type_id}',
                    `amount` = '{$this->amount}',
                    `cheq_no` = '{$this->cheq_no}',
                    `bank_id` = '{$this->bank_id}',
                    `branch_id` = '{$this->branch_id}',
                    `cheq_date` = '{$this->cheq_date}',
                    `is_settle` = '{$this->is_settle}'
                  WHERE `id` = '{$this->id}'";

        $db = new Database();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `payment_receipt_method` WHERE `id` = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                         `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                  FROM `payment_receipt_method`
                  ORDER BY `id` DESC";

        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function getByReceipt($receiptId)
    {
        $query = "SELECT `id`, `receipt_id`, `invoice_id`, `payment_type_id`, `amount`, 
                         `cheq_no`, `bank_id`, `branch_id`, `cheq_date`, `is_settle`
                  FROM `payment_receipt_method`
                  WHERE `receipt_id` = '" . (int)$receiptId . "'
                  ORDER BY `id` ASC";

        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            array_push($array, $row);
        }

        return $array;
    }

    public function updateIsSettle($id)
    {
        $query = "UPDATE `payment_receipt_method`
                  SET 
                    `is_settle` = 1
                  WHERE `id` = '{$id}'";
        $db = new Database();
        return $db->readQuery($query);
    }
}
