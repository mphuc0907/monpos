<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_profit extends CI_Controller
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

    public function convert_number_to_words($number)
    {
        $hyphen = ' ';
        $conjunction = '  ';
        $separator = ' ';
        $negative = 'âm ';
        $decimal = ' phẩy ';
        $dictionary = array(
            0 => 'Không',
            1 => 'Một',
            2 => 'Hai',
            3 => 'Ba',
            4 => 'Bốn',
            5 => 'Năm',
            6 => 'Sáu',
            7 => 'Bảy',
            8 => 'Tám',
            9 => 'Chín',
            10 => 'Mười',
            11 => 'Mười một',
            12 => 'Mười hai',
            13 => 'Mười ba',
            14 => 'Mười bốn',
            15 => 'Mười năm',
            16 => 'Mười sáu',
            17 => 'Mười bảy',
            18 => 'Mười tám',
            19 => 'Mười chín',
            20 => 'Hai mươi',
            30 => 'Ba mươi',
            40 => 'Bốn mươi',
            50 => 'Năm mươi',
            60 => 'Sáu mươi',
            70 => 'Bảy mươi',
            80 => 'Tám mươi',
            90 => 'Chín mươi',
            100 => 'trăm',
            1000 => 'ngàn',
            1000000 => 'triệu',
            1000000000 => 'tỷ',
            1000000000000 => 'nghìn tỷ',
            1000000000000000 => 'ngàn triệu triệu',
            1000000000000000000 => 'tỷ tỷ'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int)$number < 0) || (int)$number < 0 - PHP_INT_MAX) {
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . $this->convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string)$fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    public function revenue()
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
        $user = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($user['group_id'] == 1) ? 1 : (($user['parent_id'] == 1) ? $user['id'] : $user['parent_id']);


        $type = $_GET['type'];
        $custom_id = $_GET['custom_id'];
        $branch_store = $_GET['branch_store'];
        $user_store = $_GET['user_store'];
        $inventory_id = $_GET['inventory_id'];
        $starts_date = $_GET['starts_date'];
        $ends_date = $_GET['ends_date'];
        if ($type === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($custom_id === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($branch_store === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($user_store === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($inventory_id === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($starts_date === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($ends_date === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($type == 1) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(store_id) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_orders'] = $this->db
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where('shop_id', $parent)
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->result_array();
                        }
                    }
                }
            }
            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } else if ($type == 2) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $inventory_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('store_id', $inventory_id)
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $inventory_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $branch_store)
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $branch_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $inventory_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where('shop_id', $parent)
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(customer_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_customers = $this->db
                                ->select('customer_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('customer_id')
                                ->get()
                                ->result_array();
                            foreach ($list_customers as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where('customer_id', $item['customer_id'])
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_customers'][] = $item;
                            }
                        }
                    }
                }
            }

            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } else if ($type == 3) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $item['user_init'])
                                    ->where('store_id', $inventory_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('shop_id', $parent)
                                    ->where('customer_id', $custom_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('shop_id', $parent)
                                    ->where('customer_id', $custom_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $item['user_init'])
                                    ->where('customer_id', $custom_id)
                                    ->where('store_id', $inventory_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('shop_id', $parent)
                                    ->where('customer_id', $custom_id)
                                    ->where('store_id', $inventory_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $item['user_init'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $item['user_init'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $inventory_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $item['user_init'])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $item['user_init'])
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('user_init', $item['user_init'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $item['user_init'])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $item['user_init'])
                                    ->where('store_id', $inventory_id)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sale_id', $user_store)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $item['user_init'])
                                    ->where('sale_id', $user_store)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(user_init)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_users = $this->db
                                ->select('user_init, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('sell_time >=', $starts_date)
                                ->where('shop_id', $parent)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('user_init')
                                ->get()
                                ->result_array();
                            foreach ($list_users as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('user_init', $item['user_init'])
                                    ->where('shop_id', $parent)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_users'][] = $item;
                            }
                        }
                    }
                }
            }

            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } else if ($type == 4) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('shop_id', $parent)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('customer_id', $custom_id)
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('store_id', $inventory_id)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->where('store_id', $inventory_id)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('customer_id', $custom_id)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('user_init', $branch_store)
                                    ->where('store_id', $inventory_id)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('user_init', $branch_store)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('user_init', $branch_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('store_id', $inventory_id)
//                                    ->where('sale_id', $user_store)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->where('store_id', $inventory_id)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
//                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(sale_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_sales = $this->db
                                ->select('sale_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('sale_id')
                                ->get()
                                ->result_array();
                            foreach ($list_sales as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('sale_id', $item['sale_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_sales'][] = $item;
                            }
                        }
                    }
                }
            }

            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } else if ($type == 5) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('user_init', $branch_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('sale_id', $user_store)
                                    ->where('customer_id', $custom_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('customer_id', $custom_id)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('user_init', $branch_store)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('user_init', $branch_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('user_init', $branch_store)
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('user_init', $branch_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('sale_id', $user_store)
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $list_stores = $this->db
                                ->select('store_id, sum(total_money) as total_money,count(*) as total_order, sum(total_quantity) as total_quantity, sum(coupon) as total_discount, sum(lack) as total_debt')
                                ->from('orders')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->group_by('store_id')
                                ->get()
                                ->result_array();
                            foreach ($list_stores as $item) {
                                $item['_list_orders'] = $this->db
                                    ->from('orders')
                                    ->order_by('created', 'desc')
                                    ->where(['deleted' => 0, 'order_status' => 1])
                                    ->where('shop_id', $parent)
                                    ->where('store_id', $item['store_id'])
                                    ->where('sell_time >=', $starts_date)
                                    ->where('sell_time <=', $ends_date)
                                    ->get()
                                    ->result_array();
                                $data['_list_stores'][] = $item;
                            }
                        }
                    }
                }
            }

            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;

        } else if ($type == 6) {
            if ($custom_id > -1) {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(ID)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where(['report.deleted' => 0])
                                ->where('report.shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('report.user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('report.user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('report.user_init', $branch_store)
                                ->where('sale_id', $user_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('user_init', $branch_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('report.user_init', $branch_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        }
                    }
                } else {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('store_id', $inventory_id)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        }
                    } else {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('sale_id', $user_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('customer_id', $custom_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('customer_id', $custom_id)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        }
                    }
                }
            } else {
                if ($branch_store > -1) {
                    if ($inventory_id > -1) {
                        if ($user_store > -1) {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('report.user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sale_id', $user_store)
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        } else {
                            $total_orders = $this->db
                                ->select('count(distinct(store_id)) as quantity, sum(total_money) as total_money, sum(lack) as total_debt, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                                ->from('orders')
                                ->where(['deleted' => 0, 'order_status' => 1])
                                ->where('shop_id', $parent)
                                ->where('user_init', $branch_store)
                                ->where('store_id', $inventory_id)
                                ->where('sell_time >=', $starts_date)
                                ->where('sell_time <=', $ends_date)
                                ->get()
                                ->row_array();
                            $data['_list_products'] = $this->db
                                ->select('product_id, prd_name, prd_code, sum(total_money) as total_money, sum(output) as total_quantity, sum(discount) as total_discount')
                                ->from('report')
                                ->join('products', 'report.product_id=products.ID')
                                ->order_by('report.created', 'desc')
                                ->where('report.shop_id', $parent)
                                ->where(['report.deleted' => 0])
                                ->where('date >=', $starts_date)
                                ->where('date <=', $ends_date)
                                ->where('type', 3)
                                ->group_by('product_id')
                                ->get()
                                ->result_array();
                        }
                    }
                }
            }

            $data['total_orders'] = $total_orders;
            $data['type'] = $type;
            echo json_encode(array(
                "error_code" => 0,
                "data" => $data
            ), 200);
            return;
        } else {
            echo json_encode(array(
                "error_code" => 500,
                "message" => "Đã có lỗi xảy ra vui lòng kiểm tra lại"
            ), 200);
            return;
        }
    }
}