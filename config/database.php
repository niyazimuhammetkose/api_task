<?php

class Database
{
    private $host = "localhost";
    private $db_name = "api_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct()
    {
        try {
            $this->conn=
            $this->conn = mysqli_connect($this->host, $this->username, $this->password, $this->db_name) or die('MySQL connect failed. ' . mysqli_connect_error());
        } catch (mysqli_sql_exception $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    function query($sql, $die_on_error = false)
    {
        $result = mysqli_query($this->conn, $sql);
        if (!$result && $die_on_error) die(mysqli_error($this->conn));
        else return $result;
    }

    function getOne($sql, $die_on_error = false)
    {
        $result = $this->query($sql, $die_on_error);
        if (!$result) {
            return false;
        }
        $row = $this->fetchByAssoc($result);
        if (!empty($row)) {
            return array_shift($row);
        }
        return false;
    }

    function fetchByAssoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    function numRows($result)
    {
        return mysqli_num_rows($result);
    }

    function closeConnection()
    {
        mysqli_close($this->conn);
    }
}