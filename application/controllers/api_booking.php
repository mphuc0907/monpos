<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_booking extends CI_Controller
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
            case 'date_order':
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

    public function validate_date_order($value)
    {
        $date = date_parse_from_format('Y-m-d H:i:s', $value); // Phân tích chuỗi ngày tháng theo định dạng "Y/m/d"
        return $date['error_count'] === 0 && checkdate($date['month'], $date['day'], $date['year']);
    }

    public function validate_vietnamese_string($value)
    {
        $pattern = '/^[\p{L} .-]+$/u'; // Kiểm tra giá trị chỉ chứa chữ cái, chữ có dấu, " ", ".", "-"
        return preg_match($pattern, $value) === 1;
    }

    public function layKhoangThoiGianThang($thang, $nam)
    {
        if ($thang < 1 || $thang > 12) {
            return false;
        }

        $ngayBatDau = date("d", strtotime("$nam-$thang-01"));
        $ngayKetThuc = date("d", strtotime("$nam-$thang-" . date("t", strtotime("$nam-$thang-01"))));

        return [$ngayBatDau, $ngayKetThuc];
    }

//
//    function time_booking($id_time = '')
//    {
//
//        $id = self::Getid();
//        if ($id == 0) {
//
//            $output_data = array(
//                "error_code" => 400,
//                "message" => "Vui lòng đăng nhập lại"
//            );
//            echo json_encode($output_data, 200);
//            return;
//
//        }
//
//        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
//        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
//
//        if (!empty($id_time)) {
//            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//
//                echo json_encode(array(
//                    "error_code" => 405,
//                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
//                ), 200);
//                return;
//            }
//        } else {
//            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
//
//                echo json_encode(array(
//                    "error_code" => 405,
//                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
//                ), 200);
//                return;
//            }
//
//            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
//                $data = $this->db->select('id, name_time')->from('time_booking')->where('shop_id', $parent)->get()->result_array();
//                echo json_encode(array(
//                    "error_code" => 0,
//                    "data" => $data
//                ), 200);
//                return;
//            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
//                $input_data = file_get_contents("php://input");
//                $input_data = json_decode($input_data, true);
//                $this->db->trans_begin();
//                print_r(1);
//                die();
//            }
//        }
//    }

    function booking($id_book = '')
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
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);

        if (!empty($id_book)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {

                $data = $this->db->from('schedule')
                    ->where('shop_id', $parent)
                    ->where('deleted', 0)
                    ->where('id', $id_book)
                    ->get()->row_array();
                if (!empty($data)):
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $data
                    ), 200);
                    return;
                else:
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại!"
                    ), 200);
                    return;
                endif;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = $this->db->from('schedule')
                    ->where('shop_id', $parent)
                    ->where('deleted', 0)
                    ->where('id', $id_book)
                    ->get()->row_array();
                if (!empty($data)):
                    $input_data = file_get_contents("php://input");
                    $input_data = json_decode($input_data, true);
                    if (empty($input_data['id_sales'])) {
                        $input_data['id_sales'] = $data['id_sales'];
                    }
                    if (!empty($input_data['start_time'])) {
                        $input_data['start_time'] = strtotime(str_replace('/', '-', $input_data['start_time']));
                    } else {
                        $input_data['start_time'] = $data['start_time'];
                    }
                    if (!empty($input_data['end_time'])) {
                        $input_data['end_time'] = strtotime(str_replace('/', '-', $input_data['end_time']));
                    } else {
                        $input_data['end_time'] = $data['end_time'];
                    }
                    if (empty($input_data['id_location'])) {
                        $input_data['id_location'] = $data['id_location'];
                    }
                    if (empty($input_data['id_customer'])) {
                        $input_data['id_customer'] = $data['id_customer'];
                    }
                    if (empty($input_data['employeeID'])) {
                        $input_data['employeeID'] = $data['employeeID'];
                    }
                    $input_data['duration'] = $input_data['end_time'] - $input_data['start_time'];
                    if (!empty($input_data['data_services'])) {
                        $this->db->trans_begin();
                        $total_price = 0;
                        $total_price_origin = 0;
                        $detail_order = $input_data['data_services'];
                        foreach ($input_data['data_services'] as $key => $value) {
                            $total = $value['price'] - $value['discount_services'];
                            $total_price += $total;
                            $total_price_origin += $value['price'];
                        }
                        if (!empty($input_data['discount_code']) && $input_data['discount_code'] != '') {
                            $discount = $this->db->from('card_services')->where('card_code', $input_data['discount_code'])->where('deleted', 0)->get()->row_array();
                            if (empty($discount)) {
                                echo json_encode(array(
                                    "error_code" => 404,
                                    "message" => "Không tồn tại mã giảm giá, vui lòng kiểm tra lại",
                                ), 200);
                                return;
                            } else {
                                if ($discount['type_card'] == 1) {
                                    $start_dis = strtotime($discount['discount_startime']);
                                    $end_dis = strtotime($discount['discount_startime']);
                                } else {
                                    $start_dis = 0;
                                    $end_dis = strtotime($discount['experied_date']);
                                }
                                $time = time();
                                if ($time < $start_dis || $time > $end_dis) {
                                    echo json_encode(array(
                                        "error_code" => 205,
                                        "message" => "Mã chưa được áp dụng hoặc đã hết hạn sử dụng vui lòng kiểm tra lại",
                                    ), 200);
                                    return;
                                }
                                if ($discount['customerID'] == 1) {
                                    $check_customer = $this->db->from('card_customer')->where('id_card', $discount['id'])->where('id_customer', $input_data['id_customer'])->get()->row_array();
                                    if (empty($check_customer)) {
                                        echo json_encode(array(
                                            "error_code" => 401,
                                            "message" => "Mã không áp dụng cho khách hàng, vui lòng kiểm tra lại",
                                        ), 200);
                                        return;
                                    }
                                }

                                if ($discount['type_discount'] == 1) {

                                    $input_data['discount'] = ($total_price > $discount['discount']) ? ($total_price - $discount['discount']) : 0;

                                } else {
                                    $input_data['discount'] = ($total_price * $discount['discount']) / 100;
                                }
                            }
                        } else {
                            $input_data['discount_code'] = $data['discount_code'];
                            $input_data['discount'] = $data['discount'];
                        }
                        if (empty($input_data['custom_payment'])) {
                            $input_data['custom_payment'] = 0;
                        }
                        $lack = (int)$total_price - (int)$input_data['custom_payment'];
                        if ($lack == 0) {
                            $input_data['lack'] = 0;
                        } else {
                            if (!empty($input_data['pay_lack'])) {
                                $input_data['lack'] = $lack - $input_data['pay_lack'];
                            } else {
                                $input_data['lack'] = $lack;
                            }
                        }
                        $input_data['total_payment'] = $total_price;
                        $input_data['total_price_ser'] = $total_price_origin;
                        $input_data['data_services'] = json_encode($detail_order);
                    } else {
                        $lack = $data['lack'];
                        if ($lack == 0) {
                            $input_data['lack'] = 0;
                        } else {
                            if (!empty($input_data['pay_lack'])) {
                                $input_data['lack'] = $lack - $input_data['pay_lack'];
                            } else {
                                $input_data['lack'] = $lack;
                            }
                        }
                        $input_data['total_payment'] = $data['total_payment'];
                        $input_data['total_price_ser'] = $data['total_price_ser'];
                        $input_data['data_services'] = $data['data_services'];
                    }
                    unset($input_data['pay_lack']);
                    $this->db->update('schedule', $input_data, array('ID' => $id_book));
                    $order_2 = $this->db->from('schedule')->where('ID', $id_book)->limit(1)->get()->row_array();
                    if ($this->db->trans_status() === FALSE) {
                        $this->db->trans_rollback();
                        $output_data = array(
                            "error_code" => 500,
                            "message" => 'Cập nhật đặt lịch ko thành công, vui lòng kiểm tra lại',
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        $this->db->trans_commit();
                        $output_data = array(
                            "error_code" => 0,
                            "data_orders" => $order_2,
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                else:
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại!"
                    ), 200);
                    return;
                endif;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $data = $this->db->from('schedule')
                    ->where('shop_id', $parent)
                    ->where('id', $id_book)
                    ->get()->row_array();
                if (!empty($data)):
                    if ($data['deleted'] == 0) {
                        $this->db->update('schedule', array(
                            'deleted' => 1,
                        ), array('ID' => $id_book));
                        $data_2 = $this->db->from('schedule')
                            ->where('shop_id', $parent)
                            ->where('id', $id_book)
                            ->get()->row_array();
                        echo json_encode(array(
                            "error_code" => 0,
                            "message" => "Vô hiệu hóa đơn đặt lịch thành công.",
                            "data" => $data_2
                        ), 200);
                        return;
                    } else {
                        $this->db->delete('schedule', array('ID' => $id_book));
                        echo json_encode(array(
                            "error_code" => 0,
                            "message" => "Xóa đơn đặt lịch thành công."
                        ), 200);
                        return;
                    }
                else:
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại bản ghi, vui lòng kiểm tra lại!"
                    ), 200);
                    return;
                endif;
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] == 'GET') {

                $date = date('Y-m-d');
                $date_st = $date . ' 00:00:00';
                $date_en = $date . ' 23:59:59';
                $time_st = strtotime($date_st);
                $time_en = strtotime($date_en);
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;
                if (!empty($_GET['date'])) {
                    $date_st = $_GET['date'] . ' 00:00:00';
                    $date_en = $_GET['date'] . ' 23:59:59';
                    $time_st = strtotime(str_replace("/", "-", $date_st));
//                    print_r($time_st);die();
                    $time_en = strtotime(str_replace("/", "-", $date_en));
                }
                if (!empty($_GET['keyword'])) {
                    $keyword = $_GET['keyword'];
                    $arr = $this->db->from('customers')->select('id')
                        ->where("(customer_name LIKE '%{$keyword}%' OR customer_email LIKE '%{$keyword}%' OR customer_phone LIKE '%{$keyword}%')")->get()
                        ->result_array();

                    $resultArray = [];

                    foreach ($arr as $item) {
                        $id = $item['id'];
                        $resultArray[] = $id;
                    }
                    $data = $this->db->from('schedule')
                        ->where('shop_id', $parent)
                        ->where('deleted', 0)
                        ->where('start_time >=', $time_st)
                        ->where('end_time <=', $time_en)
                        ->where_in('id_customer', $resultArray)
                        ->limit($limit, $offset)
                        ->get()->result_array();

                } else {
                    $data = $this->db->from('schedule')
                        ->where('shop_id', $parent)
                        ->where('deleted', 0)
                        ->where('start_time >=', $time_st)
                        ->where('end_time <=', $time_en)
                        ->limit($limit, $offset)
                        ->get()->result_array();
                }
//                print_r($this->db->last_query());die();
                echo json_encode(array(
                    "error_code" => 0,
                    "data" => $data
                ), 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);
                if ($input_data['id_sales'] == '') {
                    $input_data['id_sales'] = $id;
                }
                $input_data['start_time'] = strtotime(str_replace('/', '-', $input_data['start_time']));
                $input_data['end_time'] = strtotime(str_replace('/', '-', $input_data['end_time']));
                $input_data['duration'] = $input_data['end_time'] - $input_data['start_time'];
                $this->db->trans_begin();
                $total_price = 0;
                $total_price_origin = 0;
                $detail_order = $input_data['data_services'];
                foreach ($input_data['data_services'] as $key => $value) {
                    $total = $value['price'] - $value['discount_services'];
                    $total_price += $total;
                    $total_price_origin += $value['price'];
                }
                if ($input_data['discount_code'] != '') {
                    $discount = $this->db->from('card_services')->where('card_code', $input_data['discount_code'])->where('deleted', 0)->get()->row_array();
                    if (empty($discount)) {
                        echo json_encode(array(
                            "error_code" => 404,
                            "message" => "Không tồn tại mã giảm giá, vui lòng kiểm tra lại",
                        ), 200);
                        return;
                    } else {
                        if ($discount['type_card'] == 1) {
                            $start_dis = strtotime($discount['discount_startime']);
                            $end_dis = strtotime($discount['discount_startime']);
                        } else {
                            $start_dis = 0;
                            $end_dis = strtotime($discount['experied_date']);
                        }
                        $time = time();
                        if ($time < $start_dis || $time > $end_dis) {
                            echo json_encode(array(
                                "error_code" => 205,
                                "message" => "Mã chưa được áp dụng hoặc đã hết hạn sử dụng vui lòng kiểm tra lại",
                            ), 200);
                            return;
                        }
                        if ($discount['customerID'] == 1) {
                            $check_customer = $this->db->from('card_customer')->where('id_card', $discount['id'])->where('id_customer', $input_data['id_customer'])->get()->row_array();
                            if (empty($check_customer)) {
                                echo json_encode(array(
                                    "error_code" => 401,
                                    "message" => "Mã không áp dụng cho khách hàng, vui lòng kiểm tra lại",
                                ), 200);
                                return;
                            }
                        }

                        if ($discount['type_discount'] == 1) {

                            $input_data['discount'] = ($total_price > $discount['discount']) ? ($total_price - $discount['discount']) : 0;

                        } else {
                            $input_data['discount'] = ($total_price * $discount['discount']) / 100;
                        }
                    }
                } else {
                    $input_data['discount'] = 0;
                }

                $lack = (int)$total_price - (int)$input_data['custom_payment'];
                $input_data['total_payment'] = $total_price;
                $input_data['total_price_ser'] = $total_price_origin;
                $input_data['shop_id'] = $parent;
                $input_data['lack'] = $lack;
                $year = date('Y');
                $input_data['year_bk'] = $year;
                $input_data['data_services'] = json_encode($detail_order);
                $count_book = $this->db->select('count(*) as total')
                    ->where('shop_id', $parent)
                    ->where('year_bk', $year)
                    ->from('schedule')
                    ->get()
                    ->row_array();
//                print_r($this->db->last_query());die();
                $order_count = $count_book['total'] + 1;
                $idcart = str_pad($order_count, 7, '0', STR_PAD_LEFT);
                $cartcode = "DH_{$year}{$idcart}";
                $input_data['code_booking'] = $cartcode;
                $this->db->insert('schedule', $input_data);
                $id_booo = $this->db->insert_id();
                $order_2 = $this->db->from('schedule')->where('ID', $id_booo)->limit(1)->get()->row_array();
                if ($input_data['status_booking'] == 1) {


                    $shop_id = $parent;
//                    $store = $this->db->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
                    $store_id = $parent;
                    $order = array();
                    $order['sale_id'] = $id;
                    $order['coupon'] = 0;
                    $order['customer_id'] = $input_data['id_customer'];
                    $order['payment_method'] = $input_data['payment_method'];
                    $order['customer_pay'] = $input_data['custom_payment'];
                    $order['order_status'] = 0;
                    $order['detail_order'] = $detail_order;
                    $detail_order_temp = $order['detail_order'];
                    $order['sell_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $order['sell_time'] = time();
                    $order['notes'] = $input_data['notes'];
                    $this->db->trans_begin();
                    $user_init = $parent;
                    $total_price = 0;
                    $total_origin_price = 0;
                    $total_quantity = 0;
                    $order['coupon'] = ($order['coupon'] == 'NaN') ? 0 : $order['coupon'];
                    if ($order['order_status'] == 1)
                        foreach ($order['detail_order'] as $item) {
                            $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $parent)->get()->row_array();
                            $sls['prd_sls'] = $item['quantity'];
                            $item['price'] = $product['prd_sell_price'];
                            $total_price += ($item['price'] - $item['discount_services']) * $item['quantity'];
                            $total_origin_price += $product['prd_origin_price'] * $item['quantity'];
                            $total_quantity += $item['quantity'];
                            $this->db->where('ID', $item['id'])->where('shop_id', $parent)->update('products', $sls);
                            $detail_order[] = $item;
                        }
                    else
                        foreach ($order['detail_order'] as $item) {
                            $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $parent)->get()->row_array();
                            $item['price'] = $product['prd_sell_price'];
                            $total_price += ($item['price'] - $item['discount_services']) * $item['quantity'];
                            $total_quantity += $item['quantity'];
                            $detail_order[] = $item;
                        }

                    $order['total_price'] = $total_price;
                    $order['total_origin_price'] = $total_origin_price;
                    $order['total_money'] = $total_price - $order['coupon'];
                    $order['total_quantity'] = $total_quantity;
                    $order['lack'] = $total_price - $order['customer_pay'] - $order['coupon'] > 0 ? $total_price - $order['customer_pay'] - $order['coupon'] : 0;
                    $order['user_init'] = $parent;
                    $order['store_id'] = $store_id;
                    $order['shop_id'] = $shop_id;
                    $order['detail_order'] = json_encode($order['detail_order']);
                    $order['output_code'] = $cartcode;
                    $this->db->insert('orders', $order);
                    $id = $this->db->insert_id();
                    $order_2 = $this->db->from('orders')->where('ID', $id)->limit(1)->get()->row_array();
                    $percent_discount = $order['coupon'] / $total_price;
                    if ($order['order_status'] == 1) {
                        $temp = array();
                        $temp['transaction_code'] = $order['output_code'];
                        $temp['transaction_id'] = $id;
                        $temp['customer_id'] = isset($order['customer_id']) ? $order['customer_id'] : 0;
                        $temp['date'] = $order['sell_date'];
                        $temp['notes'] = $order['notes'];
                        $temp['sale_id'] = $order['sale_id'];
                        $temp['user_init'] = $order['user_init'];
                        $temp['type'] = 3;
                        $temp['store_id'] = $order['store_id'];
                        $temp['shop_id'] = $order['shop_id'];
                        foreach ($detail_order_temp as $item) {
                            $report = $temp;
                            $stock = $this->db->select('quantity')->from('inventory')->where(['store_id' => $temp['store_id'], 'product_id' => $item['id'], 'shop_id' => $temp['shop_id']])->get()->row_array();
                            $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $temp['shop_id'])->get()->row_array();
                            $report['origin_price'] = $product['prd_origin_price'] * $item['quantity'];
                            $report['product_id'] = $item['id'];
                            $report['discount'] = $percent_discount * $item['quantity'] * $product['prd_sell_price'];
                            $report['price'] = $product['prd_sell_price'];
                            $report['output'] = $item['quantity'];
                            $report['stock'] = $stock['quantity'];
                            $report['total_money'] = ($report['price'] * $report['output']) - $report['discount'];
                            $this->db->insert('report', $report);
                        }
                    }
                    if ($this->db->trans_status() === FALSE) {
                        $this->db->trans_rollback();
                        $output_data = array(
                            "error_code" => 500,
                            "message" => 'Lỗi thêm đơn hàng, vui lòng kiểm tra lại',
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $output_data = array(
                        "error_code" => 500,
                        "message" => 'Lỗi thêm đơn hàng, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $this->db->trans_commit();
                    $output_data = array(
                        "error_code" => 0,
                        "data_orders" => $order_2,
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }
        }
    }

    function order_services()
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
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);

        if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input_data = file_get_contents("php://input");
            $input_data = json_decode($input_data, true);
            $shop_id = $parent;
            $store_id = $parent;
            $order = $input_data;

            $order['sale_id'] = $id;
            if ($order['coupon'] == 'NaN') {
                $order['coupon'] = 0;
            }
            if (self::check_validate($order['sale_id'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (self::check_validate($order['customer_id'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (self::check_validate($order['payment_method'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (self::check_validate($order['coupon'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (self::check_validate($order['customer_pay'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (self::check_validate($order['order_status'], 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            $detail_order_temp = $order['detail_order'];
            $order['sell_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
            $order['sell_time'] = strtotime($order['sell_date']);
            $this->db->trans_begin();
            $user_init = $parent;
            $total_price = 0;
            $total_origin_price = 0;
            $total_quantity = 0;
            $order['coupon'] = ($order['coupon'] == 'NaN') ? 0 : $order['coupon'];
            if ($order['order_status'] == 1)
                foreach ($order['detail_order'] as $item) {
                    $inventory_quantity = $this->db->select('quantity')->from('inventory')->where(['store_id' => $store_id, 'product_id' => $item['id'], 'shop_id' => $parent])->get()->row_array();
                    if (!empty($inventory_quantity)) {
                        $this->db->where(['store_id' => $store_id, 'product_id' => $item['id'], 'shop_id' => $parent])->update('inventory', ['quantity' => $inventory_quantity['quantity'] - $item['quantity'], 'user_upd' => $user_init]);
                    } else {
                        $inventory = ['store_id' => $store_id, 'product_id' => $item['id'], 'quantity' => -$item['quantity'], 'user_init' => $user_init, 'shop_id' => $parent];
                        $this->db->insert('inventory', $inventory);
                    }

                    $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $parent)->get()->row_array();
                    $sls['prd_sls'] = $product['prd_sls'] - $item['quantity'];
                    $item['price'] = $product['prd_sell_price'];
                    $total_price += ($item['price'] - $item['discount']) * $item['quantity'];
                    $total_origin_price += $product['prd_origin_price'] * $item['quantity'];
                    $total_quantity += $item['quantity'];
                    $this->db->where('ID', $item['id'])->where('shop_id', $parent)->update('products', $sls);
                    $detail_order[] = $item;
                }
            else
                foreach ($order['detail_order'] as $item) {
                    $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $parent)->get()->row_array();
                    $item['price'] = $product['prd_sell_price'];
                    $total_price += ($item['price'] - $item['discount']) * $item['quantity'];
                    $total_quantity += $item['quantity'];
                    $detail_order[] = $item;
                }

            $order['total_price'] = $total_price;
            $order['total_origin_price'] = $total_origin_price;
            $order['total_money'] = $total_price - $order['coupon'];
            $order['total_quantity'] = $total_quantity;
            $order['lack'] = $total_price - $order['customer_pay'] - $order['coupon'] > 0 ? $total_price - $order['customer_pay'] - $order['coupon'] : 0;
            $order['user_init'] = $parent;
            $order['store_id'] = $store_id;
            $order['shop_id'] = $shop_id;
            $order['detail_order'] = json_encode($detail_order);
            $this->db->select_max('output_code')->like('output_code', 'HDDV');
            $max_output_code = $this->db->where('shop_id', $shop_id)->get('orders')->row();
            $max_code = (int)(str_replace('HDDV', '', $max_output_code->output_code)) + 1;

            if ($max_code < 10)
                $order['output_code'] = 'HDDV000000' . ($max_code);
            else if ($max_code < 100)
                $order['output_code'] = 'HDDV00000' . ($max_code);
            else if ($max_code < 1000)
                $order['output_code'] = 'HDDV0000' . ($max_code);
            else if ($max_code < 10000)
                $order['output_code'] = 'HDDV000' . ($max_code);
            else if ($max_code < 100000)
                $order['output_code'] = 'HDDV00' . ($max_code);
            else if ($max_code < 1000000)
                $order['output_code'] = 'HDDV0' . ($max_code);
            else if ($max_code < 10000000)
                $order['output_code'] = 'HDDV' . ($max_code);
            $this->db->insert('orders', $order);
//            print_r($this->db->last_query());die();
            $id = $this->db->insert_id();
            $order_2 = $this->db->from('orders')->where('ID', $id)->limit(1)->get()->row_array();
            $percent_discount = $order['coupon'] / $total_price;
            if ($order['order_status'] == 1) {
                $temp = array();
                $temp['transaction_code'] = $order['output_code'];
                $temp['transaction_id'] = $id;
                $temp['customer_id'] = isset($order['customer_id']) ? $order['customer_id'] : 0;
                $temp['date'] = $order['sell_date'];
                $temp['notes'] = $order['notes'];
                $temp['sale_id'] = $order['sale_id'];
                $temp['user_init'] = $order['user_init'];
                $temp['type'] = 3;
                $temp['store_id'] = $order['store_id'];
                $temp['shop_id'] = $order['shop_id'];
                foreach ($detail_order_temp as $item) {
                    $report = $temp;
                    $stock = $this->db->select('quantity')->from('inventory')->where(['store_id' => $temp['store_id'], 'product_id' => $item['id'], 'shop_id' => $temp['shop_id']])->get()->row_array();
                    $product = $this->db->from('products')->where('ID', $item['id'])->where('shop_id', $temp['shop_id'])->get()->row_array();
                    $report['origin_price'] = $product['prd_origin_price'] * $item['quantity'];
                    $report['product_id'] = $item['id'];
                    $report['discount'] = $percent_discount * $item['quantity'] * $product['prd_sell_price'];
                    $report['price'] = $product['prd_sell_price'];
                    $report['output'] = $item['quantity'];
                    $report['stock'] = $stock['quantity'];
                    $report['total_money'] = ($report['price'] * $report['output']) - $report['discount'];
                    $this->db->insert('report', $report);
                }
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $output_data = array(
                    "error_code" => 500,
                    "message" => 'Lỗi thêm đơn hàng, vui lòng kiểm tra lại',
                );
                echo json_encode($output_data, 200);
                return;
            } else {
                $this->db->trans_commit();
                $output_data = array(
                    "error_code" => 0,
                    "data_orders" => $order_2,
                );
                echo json_encode($output_data, 200);
                return;
            }
        }
    }

    function check_booking()
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
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $time = strtotime($_GET['time']);
        if (!empty($_GET['id_employe'])) {
            $id_staff = $_GET['id_employe'];
        } else {
            $id_staff = 0;
        }
        if ($id_staff == 0) {
            $count = $this->db->from('users')->where('parent_id', $parent)->where('group_id', 5)->count_all_results();
        } else {
            $count = 1;
        }
        if ($id_staff == 0) {
            $check = $this->db->from('schedule')->where('shop_id', $parent)->where("start_time <= {$time} AND end_time >= {$time}")->count_all_results();

        } else {
            $check = $this->db->from('schedule')->where('shop_id', $parent)->where('employeeID', $id_staff)->where("start_time <= {$time} AND end_time >= {$time}")->count_all_results();
        }
        if ($check < $count) {
            echo json_encode(array(
                "error_code" => 0,
                "time_booking" => date('d-m-y H:i', $time),
                "message" => "Hiện tại khoảng thời gian này đang trống, có thể đặt dịch vụ"
            ), 200);
            return;
        } else {
            echo json_encode(array(
                "error_code" => 0,
                "time_booking" => date('d-m-y H:i', $time),
                "message" => "Hiện tại khoảng thời gian đang không trống, vui lòng chọn thời gian khác"
            ), 200);
            return;
        }
    }

    function check_month_booking()
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
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {

            echo json_encode(array(
                "error_code" => 405,
                "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if (!empty($_GET['month'])) {
            $month = $_GET['month'];
        } else {
            $month = date('m');
        }
        if (!empty($_GET['year'])) {
            $year = $_GET['year'];
        } else {
            $year = date('Y');
        }

        $khoangThoiGian = self::layKhoangThoiGianThang($month, $year);
        $date_st = $khoangThoiGian[0];
        $date_en = $khoangThoiGian[1];


        $ngayTrongThang = [];
        for ($i = $date_st; $i <= $date_en; $i++) {
            $date = sprintf("%02d", $i);
            $date_start = $year . '-' . $month . '-' . $i . ' 00:00:00';
            $date_end = $year . '-' . $month . '-' . $i . ' 23:59:59';
            $time_st = strtotime($date_start);
            $time_en = strtotime($date_end);
            $check = $this->db->from('schedule')->where('shop_id', $parent)->where('deleted', 0)->where('start_time >=', $time_st)->where('start_time <=', $time_en)->count_all_results();


            if ($check > 0) {
                $srr = true;
            } else {
                $srr = false;
            }
            $ngayTrongThang[] = array(
                'date' => $date,
                'check_services' => $srr
            );
        }
        $output_data = array(
            "error_code" => 0,
            "year" => $year,
            "month" => $month,
            "date" => $ngayTrongThang
        );
        echo json_encode($output_data, 200);
        return;

    }
}