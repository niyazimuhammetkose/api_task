<?php

class User
{
    private $table_name = "users";

    public $id;
    public $user_name;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $date_created;
    public $date_modified;
    public $deleted;

    function retrieve ($id)
    {
        global $db;
        $sql="SELECT * FROM users WHERE id='{$id}'";
        $result=$db->query($sql,true);
        $row = $db->fetchByAssoc($result);
        $this->id=$row['id'];
        $this->user_name=$row['user_name'];
        $this->first_name=$row['first_name'];
        $this->last_name=$row['last_name'];
        $this->email=$row['email'];
        $this->password=$row['password'];
        $this->date_created=$row['date_created'];
        $this->date_modified=$row['date_modified'];
        $this->deleted=$row['deleted'];
        return $this;
    }

    function save()
    {
        global $db;
        if (!empty($this->id)) {
            $sql = "UPDATE {$this->table_name} 
                        SET user_name = '{$this->user_name}',
                            first_name = '{$this->first_name}',
                            last_name = '{$this->last_name}',
                            email = '{$this->email}',
                            password = SHA2('{$this->password}',256),
                            deleted = '{$this->deleted}'
                        WHERE id= '{$this->id}'";
        } else {
            $sql = "INSERT INTO {$this->table_name} (user_name,first_name,last_name,email,password)
                        VALUES ('{$this->user_name}', '{$this->first_name}', '{$this->last_name}', '{$this->email}', SHA2('{$this->password}',256))";
        }
        $db->query($sql,true);
        return true;
    }

    function markDeleted()
    {
        $this->deleted=1;
        $this->save();
    }

}