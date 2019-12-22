<?php

namespace Monolyth\Lodger;

use PDO;
use Monolyth\Disclosure\{ Container, NotFoundException };

trait AccessesDatabase
{
    /** @var string */
    public $vendor;

    /** @var string */
    public $database;

    /** @var string */
    public $user;

    /** @var string */
    public $pass;

    protected function getPdoFromSuppliedCredentials() : PDO
    {
        if (!isset($this->vendor)) {
            $this->options("What database vendor is used?", ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'], function (string $vendor) {
                $this->vendor = $vendor;
            });
        }
        if (class_exists(Container::class)) {
            try {
                $container = new Container;
                $env = $container->get('env');
                $this->database = $this->database ?? $env->db['name'];
                $this->user = $this->user ?? $env->db['user'];
                $this->pass = $this->pass ?? $env->db['pass'];
            } catch (NotFoundException $e) {
            }
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

