<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');


class api extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    public function GetToken()
    {
        $get = getallheaders();
        if (!empty($get['authorization']) || !empty($get['Authorization'])) {
            if (isset($get['authorization'])) {
                $token = str_replace('Bearer ', '', $get['authorization']);
            } else {
                $token = str_replace('Bearer ', '', $get['Authorization']);
            }
            return $token;
        } else {
            $output_data = array(
                "error_code" => 403,
                "message" => "Bạn cần phải đăng nhập"
            );
            return json_encode($output_data, 200);
        }

    }

    public function check_validate($value, $type = 'text')
    {

        switch ($type) {
            case 'email':
                return self::validate_email($value);
            case 'phone':
                return self::validate_vietnamese_phone_number($value);
            case 'date':
                return self::validate_date_of_birth($value);
            case 'text':
                return self::validate_vietnamese_string($value);
            case 'number':
                return self::validate_numeric($value);
            //Thêm các case khác cho các loại kiểm tra khác nếu cần thiết
            default:
                return false;
        }
    }

    function validate_numeric($value)
    {
        return is_numeric($value);
    }

    public function validate_email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validate_vietnamese_phone_number($value)
    {
        $pattern = '/^(0|\+84)\d{9,10}$/'; // Kiểm tra số điện thoại bắt đầu bằng 0 hoặc +84, và có 9-10 chữ số
        return preg_match($pattern, $value) === 1;
    }

    public function validate_date_of_birth($value)
    {
        $date = date_parse_from_format('Y/m/d', $value); // Phân tích chuỗi ngày tháng theo định dạng "Y/m/d"
        return $date['error_count'] === 0 && checkdate($date['month'], $date['day'], $date['year']);
    }

    public function validate_vietnamese_string($value)
    {
        $pattern = '/^[\p{L} .-]+$/u'; // Kiểm tra giá trị chỉ chứa chữ cái, chữ có dấu, " ", ".", "-"
        return preg_match($pattern, $value) === 1;
    }

    public function Getid()
    {
        $token = self::GetToken();

        $user = $this->db->where('token_login', $token)->from('users')->get()->row_array();
        if (empty($user)) {
            return 0;
        } else {
            return $user['id'];
        }
    }

    public function api_login_user()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $input_data = file_get_contents("php://input");
        $input_data = json_decode($input_data, true);

        // Kiểm tra dữ liệu đầu vào
        if (!isset($input_data['username']) || !isset($input_data['password']) || !isset($input_data['token_services']) ) {
            echo json_encode(array(
                "error_code" => 400,
                "message" => "Trường dữ liệu bị thiếu, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $user = $this->db->where('username', $input_data['username'])->or_where('email', $input_data['username'])->from('users')->get()->row_array();
        $password = $this->cms_common_string->password_encode($input_data['password'], $user['salt']);
        // Kiểm tra tài khoản
        if (empty($user) || $password != $user['password']) {
            echo json_encode(array(

                "error_code" => 401,
                "message" => "User không tồn tại hoặc mật khẩu không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }

//        print_r($user);die();
        if ($user['parent_id'] != 1) {
            $user_2 = $this->db->where('username', $input_data['parent_user'])->or_where('email', $input_data['parent_user'])->from('users')->get()->row_array();
            if (empty($user_2)) {
                echo json_encode(array(

                    "error_code" => 403,
                    "message" => "Đã xảy ra lỗi vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($user_2->id != $user->parent_id) {
                echo json_encode(array(

                    "error_code" => 403,
                    "message" => "Đã xảy ra lỗi vui lòng kiểm tra lại"
                ), 200);
                return;
            }
        }
        $arr = json_decode($user['data_services']);
        if(empty($arr)){
            $arr[] = $input_data['token_services'];
        }
        elseif(!in_array($input_data['token_services'],$arr)){
            $arr[] = $input_data['token_services'];
        }
        if(empty($user['token_login'])) {
            $token = $this->generateToken();
        }else{
            $token = $user['token_login'];
        }
        $this->db->where('id', $user['id'])->update('users', ['token_login' => $token,'data_services' => json_encode($arr)]);
        $user = $this->db->where('token_login', $token)->from('users')->get()->row_array();
        // Trả về dữ liệu đầu ra
        $output_data = array(
            "error_code" => 0,
            "message" => "Đăng nhập thành công",
            "data" => $user
        );
        echo json_encode($output_data, 200);
        return;
    }


    public function generateToken($length = 120)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        $result .= time();
        return $result;
    }



    // Trang Tổng quan
    // Tổng quan
    public function dashboard_general()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (empty($_GET['starts_date']) || empty($_GET['ends_date'])) {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time_starts = date('Y-m-d', $_GET['starts_date']);
        $time_end = date('Y-m-d', $_GET['ends_date']);
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $today = date('Y-m-d');
        $orders = $this->db->from('orders')->where('shop_id', $parent)->where(('sell_time >='), $_GET['starts_date'])->where(('sell_time <='), $_GET['ends_date'])->where(('deleted'), 0)->get()->result_array();
//        print_r($this->db->last_query());die();
        $tongtien = 0;
        $soluongsp = array();
        foreach ($orders as $item) {
            $tongtien += (int)str_replace(",", "", $item['total_money']);
            $sps = json_decode($item['detail_order'], true);
            foreach ($sps as $sp) {
                $id = $sp['id'];
                $soluongsp[$id] = 0;
            }
        }
//        $data['lamgiaban'] = $this->db->from('products')->where('shop_id', $parent)->where(['prd_status' => 1, 'deleted' => 0, 'prd_sell_price' => 0])->count_all_results();
//        $data['lamgiamua'] = $this->db->from('products')->where('shop_id', $parent)->where(['prd_status' => 1, 'deleted' => 0, 'prd_origin_price' => 0])->count_all_results();
        $total_prd = $this->db->from('products')->where('shop_id', $parent)->where(['prd_status' => 1, 'deleted' => 0])->count_all_results();
        $data['data']['_sl_product'] = $total_prd;
        $data['data']['_sl_manufacture'] = $this->db->from('products_manufacture')->where('shop_id', $parent)->count_all_results();
        $slsitem = count($soluongsp);
        $data['sl_products_in_stock'] = count($this->db->select('ID')->where(['prd_status' => 1, 'deleted' => 0, 'prd_sls >' => 0])->where('shop_id', $parent)->from('products')->get()->result_array());
        $data['sl_products_out_stock'] = count($this->db->select('ID')->where(['prd_status' => 1, 'deleted' => 0, 'prd_sls' => 0])->where('shop_id', $parent)->from('products')->get()->result_array());
        $data['total_money'] = $tongtien;
        $data['sl_orders'] = count($orders);
        $data['sl_products'] = $slsitem;
//        $store = $this->db->from('stores')->where('shop_id', $parent)->get()->result_array();
//        $data['data']['store'] = $store;
//        $store_id = $this->db->select('ID')->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
//        if (!empty($store_id)) {
//            $data['data']['store_id'] = $store_id['ID'];
//        } else {
//            $data['data']['store_id'] = 0;
//        }
        $output_data = array(
            "error_code" => 0,
            "data" => $data
        );
        echo json_encode($output_data, 200);
    }

    // Doanh thu chart
    public function dashboard_chart()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (empty($_GET['starts_date']) || empty($_GET['ends_date'])) {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time_starts = date('Y-m-d', $_GET['starts_date']);
        $time_end = date('Y-m-d', $_GET['ends_date']);
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $chart = array();

        $time = ($_GET['ends_date'] - $_GET['starts_date']) / 6;
//        print_r($time);die();
        $time_be = $_GET['starts_date'];
        for ($i = 0; $i < 6; $i++) {
            if ($i == 5) {
                $min = $time_be;
                $max = $_GET['ends_date'];
                $time_be = $max;
            } else {
                $min = $time_be;
                $max = $time_be + $time;
                $time_be = $max;
            }
            $total_orders_min = $this->db
                ->select('count(*) as quantity, sum(total_money) as total_money, sum(total_origin_price) as total_origin_price, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                ->from('orders')
                ->where(['deleted' => 0, 'order_status' => 1])
                ->where('shop_id', $parent)
                ->where('sell_time >=', $min)
                ->where('sell_time <=', $max)
                ->get()
                ->row_array();
            $chart[] = array(
                'time_starts' => date('Y-m-d H:i', $min),
                'time_ends' => date('Y-m-d H:i', $max),
                'data' => $total_orders_min);
        }
        $output_data = array(
            "error_code" => 0,
            "data" => $chart
        );
        echo json_encode($output_data, 200);
        return;
    }

    // Danh sách sản phẩm theo doanh thu
    public function dashboard_profit()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (empty($_GET['starts_date']) || empty($_GET['ends_date'])) {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time_starts = date('Y-m-d', $_GET['starts_date']);
        $time_end = date('Y-m-d', $_GET['ends_date']);
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $data['_list_products'] = $this->db
            ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(discount) as total_discount')
            ->from('report')
            ->join('products', 'report.product_id=products.ID')
            ->order_by('report.created', 'desc')
            ->where(['report.deleted' => 0])
            ->where('report.shop_id', $parent)
            ->where('date >=', $time_starts)
            ->where('date <=', $time_end)
            ->where('type', 3)
            ->group_by('product_id')
            ->get()
            ->result_array();
        $output_data = array(
            "error_code" => 0,
            "data" => $data['_list_products']
        );
        echo json_encode($output_data, 200);
    }

    // Danh sách sản phẩm theo số lượng sản phẩm bán
    public function dashboard_product_quanity()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (empty($_GET['starts_date']) || empty($_GET['ends_date'])) {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time_starts = date('Y-m-d', $_GET['starts_date']);
        $time_end = date('Y-m-d', $_GET['ends_date']);
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $data['_list_products'] = $this->db
            ->select('product_id, prd_name, prd_code, sum(output) as total_quantity, sum(discount) as total_discount')
            ->from('report')
            ->join('products', 'report.product_id=products.ID')
            ->order_by('report.created', 'desc')
            ->where(['report.deleted' => 0])
            ->where('report.shop_id', $parent)
            ->where('date >=', $time_starts)
            ->where('date <=', $time_end)
            ->where('type', 3)
            ->group_by('product_id')
            ->get()
            ->result_array();
        $output_data = array(
            "error_code" => 0,
            "data" => $data['_list_products']
        );
        echo json_encode($output_data, 200);
    }

    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        if(empty($_GET['input_device'])){
            $output_data = array(
                "error_code" => 403,
                "message" => "Không tìm thấy thiết bị, vui lòng kiểm tra lại"
            );
            echo json_encode($output_data, 200);
            return;
        }

        $check = $this->db->select('data_services')->from('users')->where('id', $id)->get()->row_array();
        $ar = json_decode($check['data_services'],true);
        if (in_array($_GET['input_device'], $ar)) {
                // Tìm vị trí của biến trong mảng
                $index = array_search($_GET['input_device'], $ar);

                // Xóa biến tại vị trí đó
                unset($ar[$index]);
                $ar = array_values($ar);
        }else{
            $output_data = array(
                "error_code" => 404,
                "message" => "Thiết bị này ko đc đăng nhập cho tài khoản này"
            );
            echo json_encode($output_data, 200);
            return;
        }

// In ra mảng sau khi xóa
        if (empty($ar)) {
            $this->db->where('id', $id)->update('users', ['token_login' => '','data_services' => json_encode($ar)]);
        } else {
            $this->db->where('id', $id)->update('users', ['data_services' => json_encode($ar)]);
        }
        $output_data = array(
            "error_code" => 0,
            "message" => "Đăng xuất thành công"
        );
        echo json_encode($output_data, 200);
        return;
    }

    public function info()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        $display_name = $data['display_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $date_birth = $data['date_birth'];
        $gender = $data['gender'];
        $address = $data['address'];
        if (self::check_validate($email, 'email') === false) {
            echo json_encode(array(
                "error_code" => 109,
                "message" => "Email không đúng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($phone, 'phone') === false) {
            echo json_encode(array(
                "error_code" => 107,
                "message" => "Số điện thoại không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($date_birth, 'date') === false) {
            echo json_encode(array(
                "error_code" => 111,
                "message" => "Ngày sinh không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($display_name, 'text') === false) {
            echo json_encode(array(
                "error_code" => 108,
                "message" => $display_name . " không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $date_birth = strtotime($date_birth);
        $this->db->where('id', $id)->update('users', ['display_name' => $display_name, 'email' => $email, 'phone' => $phone, 'date_birth' => $date_birth, 'gender' => $gender, 'address' => $address]);
        //        // Trả về dữ liệu đầu ra
        $dta = $this->db->where('id', $id)->from('users')->get()->row_array();
        $output_data = array(
            "error_code" => 0,
            "message" => "Cập nhật thành công thành công",
            "data" => $dta
        );
        echo json_encode($output_data, 200);
        return;
    }

    public function user($id_user = '')
    {
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        if ($usser['group_id'] != 4 && $usser['group_id'] >= 3 ) {
            echo json_encode(array(
                "error_code" => 403,
                "message" => "Bạn không có quyền thực hiện chức năng này, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $parent = ($usser['group_id'] == 1) ? 1 : (($usser['parent_id'] == 1) ? $usser['id'] : $usser['parent_id']);
        if (!empty($id_user)) {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $list_us = $this->db->from('users')->where('parent_id', $parent)->where('id', $id_user)->get()->row_array();
//                print_r($this->db->last_query());die();
                $output_data = array(
                    "error_code" => 0,
                    "data" => $list_us
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $display_name = $data['display_name'];
                $phone = $data['phone'];
                $date_birth = strtotime($data['date_birth']);
                $gender = $data['gender'];

                $address = $data['address'];
                if (!empty($data['password'])) {
                    $password = $data['password'];
                }
                $group_id = $data['group_id']; //2,3,4

                if ($group_id == 1) {
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Đã có lỗi xảy ra, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if($group_id  == 4){
                    if(empty($address)) {
                        echo json_encode(array(
                            "error_code" => 400,
                            "message" => "Vui lòng nhập địa chỉ chi nhánh"
                        ), 200);
                        return;
                    }
                }
                $user_1 = $this->db
                    ->from('users')
                    ->where('parent_id', $parent)
                    ->where('id', $id_user)
                    ->get()
                    ->row_array();
                if (!empty($user_1)) {
                    $user = array();
                    $user['display_name'] = $display_name;
                    $user['phone'] = $phone;
                    $user['date_birth'] = $date_birth;
                    $user['gender'] = $gender;
                    if (!empty($password)) {
                        $user['password'] = $this->cms_common_string->password_encode($password, $user_1['salt']);
                    }
                    $user['address'] = $address;
                    $user['group_id'] = $group_id;
                    $user['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $this->db->where('id', $id_user)->update('users', $user);
                    $inserted_user = $this->db
                        ->from('users')
                        ->where('id', $id_user)
                        ->get()
                        ->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "message" => "Cập nhật thành công",
                        "data" => $inserted_user
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $inserted_user = $this->db
                    ->from('users')
                    ->where('id', $id_user)
                    ->where('parent_id', $parent)
                    ->get()
                    ->row_array();
                if (!empty($inserted_user)) {
                    if ($inserted_user['user_status'] == 1) {
                        $this->db->update('users', array(
                            'user_status' => 0
                        ), array(
                            'id' => $id_user
                        ));
                        $output_data = array(
                            "error_code" => 0,
                            "message" => "Vô hiệu hóa tài khoản thành công",
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        $this->db->delete('users', array(
                            'id' => $id_user
                        ));
                        $output_data = array(
                            "error_code" => 0,
                            "message" => "Xóa tài khoản thành công",

                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                } else {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                if (!empty($_GET['group_id'])) {
                    $group_id = $_GET['group_id'];
                }else{
                    $group_id  = '';
                }
                if(!empty($group_id)){

                    $list_us = $this->db->from('users')->where('group_id', $group_id)->where('parent_id', $parent)->get()->result_array();
                }else{
                    $list_us = $this->db->from('users')->where('parent_id', $parent)->get()->result_array();
                }

                $output_data = array(
                    "error_code" => 0,
                    "data" => $list_us
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if(!empty( $data['address'])) {
                    $address = $data['address'];
                }else{
                    $address = '';
                }

                $display_name = $data['display_name'];
                $username = $data['username'];
                $email = $data['email'];
                $group_id = $data['group_id']; //2,3,4
                if ($group_id == 1) {
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Đã có lỗi xảy ra, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    if($group_id  == 4){
                        if(empty($address)) {
                            echo json_encode(array(
                                "error_code" => 400,
                                "message" => "Vui lòng nhập địa chỉ chi nhánh"
                            ), 200);
                            return;
                        }
                    }
                    $user = $this->db
                        ->from('users')
                        ->where('parent_id', $parent)
                        ->where("(email = '$email' OR username = '$username')")
                        ->get()
                        ->row_array();;
                    if (empty($user)) {
                        $user = array();
                        $user['display_name'] = $display_name;
                        $user['username'] = $username;
                        $user['address'] = $address;
                        $user['email'] = $email;
                        $user['group_id'] = $group_id;
                        $user['parent_id'] = $parent;
                        $pass = $this->generateToken(8);
                        $user['salt'] = $this->cms_common_string->random(69);
                        $user['password'] = $this->cms_common_string->password_encode($pass, $user['salt']);
                        $user['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                        $this->db->insert('users', $user);
                        $inserted_id = $this->db->insert_id();

// Lấy thông tin chi tiết của bản ghi vừa thêm vào
                        $inserted_user = $this->db
                            ->from('users')
                            ->where('id', $inserted_id)
                            ->get()
                            ->row_array();
                        $message = "
    <html>
    <head>
        <title>Thông tin tài khoản mới</title>
    </head>
    <body>
        <p>Xin chào " . $display_name . ",</p>
        <p>Bạn đã nhận được một tài khoản mới từ " . $usser['display_name'] . ".</p>
        <p>Thông tin tài khoản:</p>
        <ul>
            <li>Tên gian hàng: " . $usser['username'] . "</li>
            <li>Tên tài khoản: " . $username . "</li>
            <li>Mật khẩu: " . $pass . "</li>
        </ul>
        <p>Vui lòng đăng nhập vào hệ thống và thay đổi mật khẩu của bạn sau khi đăng nhập.</p>
        <p>Trân trọng,</p>
        <p>" . $usser['display_name'] . "</p>
    </body>
    </html>
";
                        $param = array('name' => 'Monpos', 'from' => 'nguyenhoanglong28031998@gmail.com', 'password' => 'yuvlwgtqxwgfwqnx', 'to' => $email, 'subject' => 'Monpos - Cấp tài khoản', 'message' => $message);
                        $result = $this->cms_common->sentMail($param);
                        $output_data = array(
                            "error_code" => 0,
                            "message" => "Thêm mới thành công",
                            "data" => $inserted_user
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        echo json_encode(array(
                            "error_code" => 500,
                            "message" => "Email đã tồn tại, vui lòng tạo tài khoản với email khác"
                        ), 200);
                        return;
                    }
                }
            }
        }
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        $display_name = $data['display_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $date_birth = $data['date_birth'];
        $gender = $data['gender'];
        $address = $data['address'];
        if (self::check_validate($email, 'email') === false) {
            echo json_encode(array(
                "error_code" => 109,
                "message" => "Email không đúng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($phone, 'phone') === false) {
            echo json_encode(array(
                "error_code" => 107,
                "message" => "Số điện thoại không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($date_birth, 'date') === false) {
            echo json_encode(array(
                "error_code" => 111,
                "message" => "Ngày sinh không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (self::check_validate($display_name, 'text') === false) {
            echo json_encode(array(
                "error_code" => 108,
                "message" => $display_name . " không đúng định dạng, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $date_birth = strtotime($date_birth);
        $this->db->where('id', $id)->update('users', ['display_name' => $display_name, 'email' => $email, 'phone' => $phone, 'date_birth' => $date_birth, 'gender' => $gender, 'address' => $address]);
        //        // Trả về dữ liệu đầu ra
        $dta = $this->db->where('id', $id)->from('users')->get()->row_array();
        $output_data = array(
            "error_code" => 0,
            "message" => "Cập nhật thành công thành công",
            "data" => $dta
        );
        echo json_encode($output_data, 200);
        return;
    }

    public function activated_user($id_user)
    {
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        if ($usser['group_id'] == 3) {
            echo json_encode(array(
                "error_code" => 403,
                "message" => "Bạn không có quyền thực hiện chức năng này, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $parent = ($usser['group_id'] == 1) ? 1 : (($usser['parent_id'] == 1) ? $usser['id'] : $usser['parent_id']);
        if (!empty($id_user)) {
            $inserted_user = $this->db
                ->from('users')
                ->where('parent_id', $parent)
                ->where('id', $id_user)
                ->get()
                ->row_array();
            if (!empty($inserted_user)) {
                $this->db->update('users', array(
                    'user_status' => 1
                ), array(
                    'id' => $id_user
                ));
                $output_data = array(
                    "error_code" => 0,
                    "message" => "Kích hoạt tài khoản thành công"
                );
                echo json_encode($output_data, 200);
                return;
            } else {
                echo json_encode(array(
                    "error_code" => 404,
                    "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
        } else {
            echo json_encode(array(
                "error_code" => 404,
                "message" => "Đã có lỗi xảy ra, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
    }

    public function info_bank()
    {
//        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
//
//            echo json_encode(array(
//                "error_code" => 405,
//                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
//            ), 200);
//            return;
//        }
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $user = $this->db->from('users')->where('id', $id)->get()->row_array();
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {

            if (!empty($user['data_banks'])) {
                $data = json_decode($user['data_banks']);
            } else {
                $data = array();
            }
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($user['group_id'] != 4) {
                echo json_encode(array(
                    "error_code" => 403,
                    "message" => "Bạn không có quyền thực hiện chức năng này"
                ), 200);
                return;
            }
            $data = file_get_contents("php://input");
            $data = json_decode($data, true);
            $data_banks = $data['banks'];
            $this->db->where('id', $id)->update('users', ['data_banks' => json_encode($data_banks)]);
            $user = $this->db->from('users')->where('id', $id)->get()->row_array();
            $output_data = array(
                "error_code" => 0,
                "message" => "Cập nhật thành công thành công",
                "data" => $user
            );
            echo json_encode($output_data, 200);
            return;
        } else {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }

    }

    public function forgot_pass()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $input_data = file_get_contents("php://input");
        $input_data = json_decode($input_data, true);
        if (!isset($input_data['stall_name']) || !isset($input_data['email'])) {
            echo json_encode(array(
                "error_code" => 400,
                "message" => "Trường dữ liệu bị thiếu, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $nam = $input_data['stall_name'];
        $email = $input_data['email'];
        $parent_id = $this->db->select('id')->from('users')->where('username', $nam)->where('group_id', 4)->get()->row_array();
        if (empty($parent_id)) {
            echo json_encode(array(
                "error_code" => 404,
                "message" => "Dữ liệu không tồn tại, vui lòng kiểm tra lại!"
            ), 200);
            return;
        }
        $user = $this->db->from('users')->where('email', $email)->get()->row_array();
        if (!empty($user) && $user['parent_id'] != 1) {
            $user = $this->db->from('users')->where('email', $email)->where('parent_id', $parent_id['id'])->get()->row_array();
        }

        if (empty($user)) {
            echo json_encode(array(
                "error_code" => 404,
                "message" => "Dữ liệu không tồn tại, vui lòng kiểm tra lại!"
            ), 200);
            return;
        }
        $token = $this::generateToken(160);
        $time = time() + 600;
//        print_r($token);die();
        $this->db->update('users', array(
            'token_pass' => $token,
            'time_pass' => $time
        ), 'id = ' . $user['id']);

        $link = CMS_BASE_URL . 'authentication/forgot?email=' . $email . '&token=' . $token;
        $body = '<div class="flex-1 overflow-hidden">
    <div class="react-scroll-to-bottom--css-zdunt-79elbk h-full dark:bg-gray-800">
        <div class="react-scroll-to-bottom--css-zdunt-1n7m0yu">
            <div class="flex flex-col text-sm dark:bg-gray-800">
                <div class="group w-full text-token-text-primary border-b border-black/10 dark:border-gray-900/50 bg-gray-50 dark:bg-[#444654]">
                    <div class="flex p-4 gap-4 text-base md:gap-6 md:max-w-2xl lg:max-w-[38rem] xl:max-w-3xl md:py-6 lg:px-0 m-auto">
                        <div class="relative flex w-[calc(100%-50px)] flex-col gap-1 md:gap-3 lg:w-[calc(100%-115px)]">
                            <div class="flex flex-grow flex-col gap-3">
                                <div class="min-h-[20px] flex flex-col items-start gap-3 overflow-x-auto whitespace-pre-wrap break-words">
                                    <div class="markdown prose w-full break-words dark:prose-invert light">

                                        Chào bạn,

                                        Chúng tôi nhận thấy rằng bạn đã quên mật khẩu của tài khoản. Đừng lo lắng, chúng
                                        tôi sẽ giúp bạn tạo mật khẩu mới trong vòng 10 phút.

                                        Để khôi phục mật khẩu, bạn chỉ cần thực hiện các bước sau đây trong thời gian
                                        ngắn:
                                        <ol>
                                            <li>Nhấp vào đường dẫn sau để truy cập trang khôi phục mật khẩu:
                                                ' . $link . '
                                            </li>
                                            <li>Trang web sẽ mở ra và yêu cầu bạn nhập mật khẩu mới. Vui lòng nhập một
                                                mật khẩu mà bạn có thể nhớ dễ dàng nhưng không dễ dàng bị đoán đúng.
                                            </li>
                                            <li>Xác nhận mật khẩu mới của bạn bằng cách nhập lại nó một lần nữa.</li>
                                            <li>Sau khi bạn đã nhập mật khẩu mới và xác nhận mật khẩu, hãy nhấn nút "Xác
                                                nhận" hoặc "Hoàn tất" (hoặc tương tự) để hoàn thành quá trình khôi phục
                                                mật khẩu.
                                            </li>
                                        </ol>
                                        Vui lòng nhớ rằng đường dẫn khôi phục mật khẩu sẽ chỉ có hiệu lực trong vòng 10
                                        phút kể từ khi bạn nhận được email này. Sau thời gian này, bạn sẽ cần yêu cầu
                                        khôi phục mật khẩu lại.

                                        Nếu bạn không yêu cầu khôi phục mật khẩu và tin rằng đây là một email sai sót,
                                        vui lòng bỏ qua email này.
                                        Trân trọng, Nhóm Hỗ trợ của chúng tôi

                                        P.S. Để đảm bảo an toàn cho tài khoản của bạn, vui lòng không tiết lộ mật khẩu
                                        cho bất kỳ ai và chú ý đến việc bảo mật thông tin cá nhân của bạn.

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
        $param = array('name' => 'Monpos', 'from' => 'nguyenhoanglong28031998@gmail.com', 'password' => 'yuvlwgtqxwgfwqnx', 'to' => $email, 'subject' => 'Monpos - Quên mật khẩu', 'message' => $body);
        $result = $this->cms_common->sentMail($param);
        echo json_encode(array(
            "error_code" => 0,
            "message" => "Yêu cầu khôi phục mật khẩu đã được gửi qua email. Vui lòng kiểm tra hộp thư đến của bạn."
        ), 200);
        return;
    }

    public function rep_pass()
    {
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $data = file_get_contents("php://input");
            $data = json_decode($data, true);
            $old_pass = $data['old_pass'];
            $new_pass = $data['new_pass'];
            $re_pass = $data['re_pass'];
            if ($new_pass !== $re_pass) {
                $output_data = array(
                    "error_code" => 104,
                    "message" => "Trường dũ liệu truyền lên ko chính xác, vui lòng kiểm tra lại!"
                );
                echo json_encode($output_data, 200);
                return;
            }
            $password = $this->cms_common_string->password_encode($old_pass, $usser['salt']);
            if ($password != $usser['password']) {
                $output_data = array(
                    "error_code" => 105,
                    "message" => "Mật khẩu không chính xác, vui lòng kiểm tra lại!"
                );
                echo json_encode($output_data, 200);
                return;
            } else {
                $password_n = $this->cms_common_string->password_encode($new_pass, $usser['salt']);
                $this->db->update('users', array(
                    'password' => $password_n
                ), array(
                    'id' => $id
                ));
                $output_data = array(
                    "error_code" => 0,
                    "message" => "Cập nhật mật khẩu thành công"
                );
                echo json_encode($output_data, 200);
                return;
            }
        } else {
            $output_data = array(
                "error_code" => 405,
                "message" => "Phương thức truyền không đúng, vui lòng kiểm tra lại!"
            );
            echo json_encode($output_data, 200);
            return;
        }

    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $input_data = file_get_contents("php://input");
        $input_data = json_decode($input_data, true);
        $user = $this->db
            ->select('username, password, salt')
            ->where("(username = '{$input_data['username']}' OR email = '{$input_data['email']}') AND group_id = 4", NULL, FALSE)
            ->from('users')
            ->get()
            ->row_array();
        if (empty($user)) {
            $user['display_name'] = $input_data['display_name'];
            $user['username'] = $input_data['username'];
            $user['email'] = $input_data['email'];
            $user['group_id'] = 4;
            $user['parent_id'] = 1;
            $arr = array();
            if (!empty($input_data['token_services'])) {
                $arr[] = $input_data['token_services'];
            }
            $user['data_services'] = json_encode($arr);
            $user['group_id'] = 4;
            $user['token_login'] = $this->generateToken();

            $user['salt'] = $this->cms_common_string->random(69);
            $user['password'] = $this->cms_common_string->password_encode($input_data['password'], $user['salt']);
            $user['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
            $this->db->insert('users', $user);
            $newestUserId = $this->db->insert_id();
            if ($newestUserId) {
                // Sử dụng ID này để truy xuất thông tin của bản ghi mới nhất
                $query = "SELECT * FROM cms_users WHERE id = $newestUserId";
                $result = $this->db->query($query);

                if ($result->num_rows() > 0) {
                    $newestUser = $result->row();
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $newestUser
                    ), 200);
                    return;
                }
            } else {
                echo json_encode(array(
                    "error_code" => 404,
                    "message" => "Dữ liệu thêm mới không thành công"
                ), 200);
                return;
            }
        } else {
            echo json_encode(array(
                "error_code" => 400,
                "message" => "email hoặc username đã tồn tại, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
    }

    public function firebasecm($token_notification = '', $message = ''){
        $serverKey = "AAAAGFDz5Y0:APA91bGkAhNcfiK4iUNlYPGoA34022W8T01JIOTGiMashFH4W_5E30w--FoOc2l7uewl6s0CSuRiED2BXq-NwzvWQibGvr5p7YrPhqFziDSQQb8HEH3XHVrIxzVsfbHgR4bBLn9KD5bP";

        if(empty($token_notification) && empty($token_notification) ){
            $id = self::Getid();
            if ($id == 0) {

                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }

            $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);

            if ($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if($_SERVER['REQUEST_METHOD'] == 'GET'){
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;
                $data = $this->db->from('notification')
                    ->where('shop_id', $parent)
                    ->where('status_notification', 0)
                    ->limit($limit, $offset)
                    ->get()->result_array();
                echo json_encode(array(
                    "error_code" => 0,
                    "data" => $data
                ), 200);
                return;
            }else{

                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);

                $input_data['status_notification'] = 0;
                $input_data['time_push'] = strtotime(str_replace('/','-', $input_data['time_push']));
                $input_data['shop_id'] = $parent;

                $this->db->trans_begin();
                $this->db->insert('notification', $input_data);
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $output_data = array(
                        "error_code" => 500,
                        "message" => 'Lỗi thêm thông báo không thành công, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $this->db->trans_commit();
                    $output_data = array(
                        "error_code" => 0,
                        "message" => "Thêm thông báo thành công",
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }
        }else {
            $curl = curl_init();

            $msg = array(
                'title' => $message['title'],
                'body' => $message['description'],
                'sound' => 'default'
            );
            $fields = array();
            $fields['to'] = $token_notification;
            $fields['collapse_key'] = 'type_a';
            $fields['notification'] = $msg;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($fields),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: key=" . $serverKey,
                    "Content-Type: application/json",
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        }
    }

    public function cronjob_notification(){
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;
        }
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
        if ( $_SERVER['REQUEST_METHOD'] != 'GET') {
            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time = time();
        $time_del = $time - (86400 * 30);
        $this->db->where('shop_id', $parent)->where('time_push <=',$time_del)->delete('notification');
        $arr =  $this->db->from('notification')->where('shop_id', $parent)->where('status_notification', 0)->where('time_push <=',$time)->get()->result_array();
        if(!empty($arr)){
            foreach ($arr as $value){
                $message = array(
                    'title' => $value['title'],
                    'description' =>$value['content_notification']
                );
                $response = self::firebasecm($value['token_devices'],$message);
                $check = json_decode($response);
                if($check->success == 1){
                  $this->db->update('notification',array(
                      'status_notification' => 1
                  ),array(
                      'id' => $value['id'],
                      'shop_id' =>$parent
                  ));
                }
            }
        }
        echo json_encode(array(
            "error_code" => 0,
            "message" => "Gửi thông báo thành công."
        ), 200);
        return;
    }
    public function push_notification(){
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $input_data = file_get_contents("php://input");
        $input_data = json_decode($input_data, true);
        if(empty($input_data['title'])){
            echo json_encode(array(
                "error_code" => 114,
                "message" => "Thiếu trường dữ liệu tiêu đề thông báo"
            ), 200);
            return;
        }
        if(empty($input_data['content'])){
            echo json_encode(array(
                "error_code" => 114,
                "message" => "Thiếu trường dữ liệu nội dung  thông báo"
            ), 200);
            return;
        }
        if(empty($input_data['token_device'])){
            echo json_encode(array(
                "error_code" => 114,
                "message" => "Thiếu trường dữ liệu token thiết bị"
            ), 200);
            return;
        }
        $message = array(
            'title' => $input_data['title'],
            'description' =>$input_data['content']
        );
        $response = self::firebasecm($input_data['token_device'],$message);
        $check = json_decode($response);
        if($check->success == 1){
            echo json_encode(array(
                "error_code" => 0,
                "message" => "Gửi thông báo thành công."
            ), 200);
            return;
        }else{
            echo json_encode(array(
                "error_code" => 500,
                "message" => "Lỗi, gửi thông báo không thành công. vui lòng kiểm tra lại"
            ), 200);
            return;
        }
    }
    public function branch_address(){
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $usser = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser['group_id'] == 1) ? 1 : (($usser['parent_id'] == 1) ? $usser['id'] : $usser['parent_id']);
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $list_us = $this->db->select('id, address')->from('users')->where("id = {$parent} OR (group_id = 4 AND parent_id = {$parent})")->get()->result_array();
            $output_data = array(
                "error_code" => 0,
                "data" => $list_us
            );
            echo json_encode($output_data, 200);
            return;
        }
    }
}