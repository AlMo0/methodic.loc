<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends CI_Controller
{
    public function index($x = 5)
    {
        echo "Ответ таков: " . $x;
    }
}