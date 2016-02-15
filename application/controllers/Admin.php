<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function index()
    {
        $this->load->view('home_view');
    }

    function about($age = "Нет возраста")
    {
        $data['name'] = "Александр";
        $data['surname'] = "Москаленко";
        $data['age'] = $age;


        $this->load->view('about_view', $data);
    }

    public function admin()
    {
        /*$config['image_library'] = 'gd2';
        $config['source_image'] = '/assembly/images/profil.jpg';
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['width']	= 65;
        $config['height']	= 50;

        $this->load->library('image_lib',$config);
        $this->image_lib->resize();*/

        $this->load->view('admin/admin_index_view');
    }
}