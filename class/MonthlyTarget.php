<?php

class MonthlyTarget
{
    public $id;
    public $month;
    public $target;
    public $target_commission;
    public $supper_target;
    public $supper_target_commission;
    public $collection_target;
    public $sales_executive_id;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `monthly_targets` WHERE `id` = " . (int) $id;
            $db = new Database();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->month = $result['month'];
                $this->target = $result['target'];
                $this->target_commission = $result['target_commission'];
                $this->supper_target = $result['supper_target'];
                $this->supper_target_commission = $result['supper_target_commission'];
                $this->collection_target = $result['collection_target'];
                $this->sales_executive_id = $result['sales_executive_id'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $query = "INSERT INTO `monthly_targets` (
                    `month`, 
                    `target`, 
                    `target_commission`, 
                    `supper_target`, 
                    `supper_target_commission`, 
                    `collection_target`, 
                    `sales_executive_id`, 
                    `created_at`, 
                    `updated_at`
                  ) VALUES (
                    '{$this->month}', 
                    '{$this->target}', 
                    '{$this->target_commission}', 
                    '{$this->supper_target}', 
                    '{$this->supper_target_commission}', 
                    '{$this->collection_target}', 
                    '{$this->sales_executive_id}', 
                    NOW(), 
                    NOW()
                  )";

        $db = new Database();
        return $db->readQuery($query) ? mysqli_insert_id($db->DB_CON) : false;
    }

    public function update()
    {
        $query = "UPDATE `monthly_targets` SET 
                    `month` = '{$this->month}', 
                    `target` = '{$this->target}', 
                    `target_commission` = '{$this->target_commission}', 
                    `supper_target` = '{$this->supper_target}', 
                    `supper_target_commission` = '{$this->supper_target_commission}', 
                    `collection_target` = '{$this->collection_target}', 
                    `sales_executive_id` = '{$this->sales_executive_id}', 
                    `updated_at` = NOW()
                  WHERE `id` = '{$this->id}'";

        $db = new Database();
        return $db->readQuery($query);
    }

    public function delete()
    {
        $query = "DELETE FROM `monthly_targets` WHERE `id` = '{$this->id}'";
        $db = new Database();
        return $db->readQuery($query);
    }

    public function all()
    {
        $query = "SELECT * FROM `monthly_targets` ORDER BY `month` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            $array[] = $row;
        }

        return $array;
    }

    public function bySalesExecutive($sales_executive_id)
    {
        $query = "SELECT * FROM `monthly_targets` 
                  WHERE `sales_executive_id` = '{$sales_executive_id}' 
                  ORDER BY `month` DESC";
        $db = new Database();
        $result = $db->readQuery($query);
        $array = [];

        while ($row = mysqli_fetch_array($result)) {
            $array[] = $row;
        }

        return $array;
    }
}
