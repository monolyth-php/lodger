<?php

namespace Monolyth\Lodger;

use PDO;

trait AccessesDatabase
{
    public string $vendor;

    public string $database;

    public string $user;

    public string $pass;

    protected function getPdoFromSuppliedCredentials() : PDO
    {
        if (!isset($this->vendor)) {
            $this->options("What database vendor is used?", ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'], function (string $vendor) {
                $this->vendor = $vendor;
            });
        }
        if (!isset($this->database)) {
            $this->ask('Name of the database?', function (string $database) : void {
                $this->database = $database;
            });
        }  
        if (!isset($this->user)) {
            $this->ask('User?', function (string $user) : void {
                $this->user = $user;
            });
        }  
        if (!isset($this->pass)) {
            $this->ask('Password?', function (string $pass) : void {
                $this->pass = $pass;
            });
        }
        try {
            return new PDO("{$this->vendor}:dbname={$this->database}", $this->user, $this->pass);
        } catch (PDOException $e) {
            fwrite(STDERR, "Fatal: permission denied to {$this->database} for user {$this->user} with password {$this->pass}.\n");
            exit(1);
        }
    }
}

