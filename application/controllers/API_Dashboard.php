<?php use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class API_Dashboard extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }
    public function tesst(){
        echo 1;
        die();
    }
}