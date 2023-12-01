<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_report extends CI_Controller
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

    function updateOrAddChildArray($parentArray, $childArray, $key)
    {
//        print_r($childArray);die();
        $childId = $childArray->$key;
        // Kiểm tra xem mảng con đã tồn tại trong mảng cha chưa
        $existingChildIndex = -1;
        if (!empty($parentArray)) {

            foreach ($parentArray as $index => $item) {
                if ($item->$key === $childId) {
                    $existingChildIndex = $index;
                    break;
                }
            }
        }

        if ($existingChildIndex !== -1) {

            // Mảng con đã tồn tại, cập nhật dữ liệu
            $parentArray[$existingChildIndex]->price += $childArray->price;
            $parentArray[$existingChildIndex]->quantity += $childArray->quantity;
        } else {
            // Mảng con chưa tồn tại, thêm mới vào mảng cha
            $parentArray[] = $childArray;
        }
        return $parentArray;
    }

    public function end_of_day_report()
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
        $data = array();
        $user = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($user['group_id'] == 1) ? 1 : (($user['parent_id'] == 1) ? $user['id'] : $user['parent_id']);
        $starts_date = $_GET['starts_date'];
        $ends_date = $_GET['ends_date'];

        $data_thu = $this->db->select('sum(tongtien) as total_revenue')->from('thuchi')
            ->where('hinhthuc', 0)->where('shop_id', $parent)->where('thuchi_time >=', $starts_date)
            ->where('thuchi_time <=', $ends_date)->get()->row_array();
        $data_chi = $this->db->select('sum(tongtien) as total_cost')->from('thuchi')
            ->where('hinhthuc', 1)->where('shop_id', $parent)->where('thuchi_time >=', $starts_date)
            ->where('thuchi_time <=', $ends_date)->get()->row_array();
        $thuchi = $data_thu['total_revenue'] - $data_chi['total_cost'];
        $data['revenue'] = (!empty($data_thu['total_revenue'])) ? $data_thu['total_revenue'] : 0;
        $data['cost'] = (!empty($data_chi['total_cost'])) ? $data_chi['total_cost'] : 0;
        $data['total_rev_cos'] = $thuchi;
        $data_money = $this->db->select('sum(customer_pay) as total_money')->from('orders')
            ->where('payment_method', 1)->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->row_array();
        $data['total_money'] = (!empty($data_money['total_money'])) ? $data_money['total_money'] : 0;
        $data_money_card = $this->db->select('sum(customer_pay) as total_money_card')->from('orders')
            ->where('payment_method', 2)->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->row_array();

        $data['total_money_card'] = (!empty($data_money_card['total_money_card'])) ? $data_money_card['total_money_card'] : 0;
        $data_order = $this->db->from('orders')->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->count_all_results();
        $data['count_order'] = (!empty($data_order)) ? $data_order : 0;
        $data_prod = $this->db->select('sum(total_quantity) as total_prod')->from('orders')->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->row_array();
        $data['count_prod'] = (!empty($data_prod['total_prod'])) ? $data_prod['total_prod'] : 0;
        $data_total = $this->db->select('sum(total_money) as total_money_pay')->from('orders')
            ->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->row_array();
        $data['total_revenue'] = (!empty($data_total['total_money_pay'])) ? $data_total['total_money_pay'] : 0;
        $data_customer_pay = $this->db->select('sum(customer_pay) as total_cus_pay')->from('orders')
            ->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->row_array();
        $data['total_real_money'] = (!empty($data_customer_pay['total_cus_pay'])) ? $data_customer_pay['total_cus_pay'] : 0;
        $output_data = array(
            "error_code" => 0,
            "data_report" => $data,
        );
        echo json_encode($output_data, 200);
        return;
    }

    public function report_by_order()
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
        $data = array();
        $user = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($user['group_id'] == 1) ? 1 : (($user['parent_id'] == 1) ? $user['id'] : $user['parent_id']);
        $starts_date = $_GET['starts_date'];
        $ends_date = $_GET['ends_date'];
        $data_order = $this->db->from('orders')->where('shop_id', $parent)->where('sell_time >=', $starts_date)->where(['deleted' => 0, 'order_status' => 1])
            ->where('sell_time <=', $ends_date)->get()->result_array();
        $chart = array();
        $time = ($_GET['ends_date'] - $_GET['starts_date'])/6;
//        print_r($time);die();
        $time_be =  $_GET['starts_date'];
        for ($i = 0;$i<6;$i++){
            if($i == 5){
                $min = $time_be;
                $max = $_GET['ends_date'];
                $time_be = $max;
            }else{
                $min = $time_be;
                $max = $time_be + $time;
                $time_be = $max;
            }
            $total_orders_min = $this->db
                ->select('count(*) as quantity, sum(total_money) as total_money, sum(total_origin_price) as total_origin_price, sum(coupon) as total_discount, sum(total_quantity) as total_quantity')
                ->from('orders')
                ->where(['deleted' => 0, 'order_status' => 1])
                ->where('shop_id',$parent)
                ->where('sell_time >=',$min)
                ->where('sell_time <=', $max)
                ->get()
                ->row_array();
            $total_orders_min['total_profit'] =  ($total_orders_min['total_money'] - $total_orders_min['total_discount'] - $total_orders_min['total_origin_price'] );
            $chart[] =array(
                'time_starts' => date('Y-m-d H:i',$min ),
                'time_ends' =>  date('Y-m-d H:i',$max ),
                'data'=> $total_orders_min);
        }
        $doanhthu = $von = 0;
        foreach ($data_order as $value){
            $von += $value['total_origin_price'] ;
            $doanhthu += ($value['total_money'] - $value['coupon']);
        }
        $loinhuan = $doanhthu - $von;
        $this->db->select('COUNT(*) as quantity, SUM(total_money) as total_money, SUM(total_origin_price) as total_origin_price, SUM(coupon) as total_discount, SUM(total_quantity) as total_quantity, sale_id');
        $this->db->from('orders');
        $this->db->where('deleted', 0);
        $this->db->where('order_status', 1);
        $this->db->where('shop_id', $parent);
        $this->db->where('sale_id !=', 0);
        $this->db->where('sale_id !=', $parent);
        $this->db->where('sell_time >=', $starts_date);
        $this->db->where('sell_time <=', $ends_date);
        $this->db->group_by('sale_id');
        $data_sale = $this->db->get()->result_array();
        foreach ($data_sale as $key => $value){
            $data_sale_id = $this->db->select('display_name')->from('users')->where('ID',$value['sale_id'])->where('parent_id',$parent)->get()->row_array();
            $data_sale[$key]['display_name'] = $data_sale_id['display_name'];
            $data_sale[$key]['total_profit'] = $value['total_money'] - $value['total_discount'] ;

        }
        $data_arr = array(
            'chart' =>$chart,
            'profit' =>array(
                'origin' => $von,
                'sale' =>$doanhthu,
                'profit' =>$loinhuan
            ),
            'sale_user' =>$data_sale
        );
        $output_data = array(
            "error_code" => 0,
            "data_report" => $data_arr,
        );
        echo json_encode($output_data, 200);
        return;
    }

    public function report_by_products()
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
        $data = array();
        $user = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($user['group_id'] == 1) ? 1 : (($user['parent_id'] == 1) ? $user['id'] : $user['parent_id']);
        $starts_date = $_GET['starts_date'];
        $ends_date = $_GET['ends_date'];
        $data_order = $this->db->select('detail_order')->from('orders')->where('shop_id', $parent)->where('sell_time >=', $starts_date)
            ->where('sell_time <=', $ends_date)->get()->result_array();
        foreach ($data_order as $value) {
            $dt = json_decode($value['detail_order']);
            foreach ($dt as $key => $item) {
                $order_2 = $this->db->from('products')->where('ID', $item->id)->where('shop_id', $parent)->get()->row_array();
                $dt[$key]->name = $order_2['prd_name'];
                $data = self::updateOrAddChildArray($data, $item, 'id');

            }
        }
        // Hàm so sánh để sắp xếp theo trường price

        function sortByPrice($a, $b)
        {
            if ($a->price == $b->price) {
                return 0;
            }
            return ($a->price > $b->price) ? -1 : 1;
        }

        function sortBytotal_sell_price($a, $b)
        {
            if ($a->total_sell_price == $b->total_sell_price) {
                return 0;
            }
            return ($a->total_sell_price > $b->total_sell_price) ? -1 : 1;
        }

// Sắp xếp mảng theo giá
        usort($data, 'sortByPrice');
        $data_2 = $this->db
            ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
            ->from('inventory')
            ->join('products', 'products.ID=inventory.product_id', 'INNER')
            ->where('deleted', 0)
            ->where('inventory.shop_id', $parent)
            ->order_by('inventory.created', 'desc')
            ->get()->result_array();

        $totaloinvent = $totalsinvent = $sls = 0;
        foreach ($data_2 as $key => $item) {
            if ($item['quantity'] > 0) {
                $sls += $item['quantity'];
                $totaloinvent += ($item['quantity'] * $item['prd_origin_price']);
                $totalsinvent += ($item['quantity'] * $item['prd_sell_price']);
                $data_2[$key]['total_org_price'] = $item['quantity'] * $item['prd_origin_price'];
                $data_2[$key]['total_sell_price'] = $item['quantity'] * $item['prd_sell_price'];
            } else {
                $data_2[$key]['total_org_price'] = 0;
                $data_2[$key]['total_sell_price'] = 0;
            }
        }
        foreach ($data_2 as $k => $ite) {
            $data_2[$k] = (object)$ite;
        }
        usort($data_2, 'sortBytotal_sell_price');
        $data_rp['count_product_invent'] = $sls;
        $data_rp['total_origin_price'] = $totaloinvent;
        $data_rp['total_sell_price'] = $totalsinvent;
        $data_rp['report_pro_order'] = $data;
        $data_rp['report_pro_invent'] = $data_2;
        $output_data = array(
            "error_code" => 0,
            "data_report" => $data_rp,
        );
        echo json_encode($output_data, 200);
        return;

    }
}