<?php
use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_sale_orders extends CI_Controller
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

    public function orders($id_sp = '')
    {
        if (!empty($id_sp)) {
            if (self::check_validate($id_sp, 'number') === false) {
                echo json_encode(array(
                    "error_code" => 101,
                    "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $order = $this->db->from('orders')->where('ID', $id_sp)->get()->row_array();
                $data['_list_products'] = array();

                if (isset($order) && count($order)) {
                    $list_products = json_decode($order['detail_order'], true);

                    foreach ($list_products as $product) {
                        $_product = cms_finding_productbyID($product['id']);
                        $_product['quantity'] = $product['quantity'];
                        $_product['price'] = $product['price'];
                        $data['_list_products'][] = $_product;
                        $order['list_product'][] = $product['id'];
                    }
                }
                $output_data = array(
                    "error_code" => 0,
                    "data_orders" => $order,
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);
                if (!isset($input_data['lack_pay'])) {
                    echo json_encode(array(
                        "error_code" => 400,
                        "message" => "Trường dữ liệu bị thiếu, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($input_data['lack_pay'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
                $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
                $order = $this->db->from('orders')->where('ID', $id_sp)->where('shop_id', $parent)->get()->row_array();

                if (isset($order) && count($order)) {
                    $lack = $order['lack'] - $input_data['lack_pay'];
                    if ($lack < 0) {
                        $lack = 0;
                    }
                    $custom_pay = $order['customer_pay'] + $input_data['lack_pay'];
                    if ($custom_pay > $order['total_price']) {
                        $custom_pay = $order['total_price'];
                    }
                    $this->db->where('ID', $id_sp)->where('shop_id', $parent)->update('orders', ['lack' => $lack, 'customer_pay' => $custom_pay]);

                }
                $order = $this->db->from('orders')->where('ID', $id_sp)->where('shop_id', $parent)->get()->row_array();
                $output_data = array(
                    "error_code" => 0,
                    "message" => "Update thành công.",
                    "data_orders" => $order,

                );
                echo json_encode($output_data, 200);
            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

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
                $status = $_GET['status_orders'];

                $id = self::Getid();
                if ($id == 0) {

                    $output_data = array(
                        "error_code" => 400,
                        "message" => "Vui lòng đăng nhập lại"
                    );
                    echo json_encode($output_data, 200);
                    return;

                }
                if (empty($_GET['ends_date'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['starts_date'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($status, 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($_GET['starts_date'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($_GET['ends_date'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
                $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);

                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;

//        if ($option['customer_id'] >= 0) {
//            if ($option['option1'] == '0') {
//                if ($option['date_from'] != '' && $option['date_to'] != '') {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where('deleted', 0)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where('deleted', 0)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                } else {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where('shop_id', $parent)
//                        ->where('deleted', 0)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where('deleted', 0)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                }
//            } else if ($option['option1'] == '1') {
//                if ($option['date_from'] != '' && $option['date_to'] != '') {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where('deleted', 1)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where('deleted', 1)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                } else {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where('deleted', 1)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where('deleted', 1)
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                }
//            } else if ($option['option1'] == '2') {
//                if ($option['date_from'] != '' && $option['date_to'] != '') {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where(['deleted' => 0, 'lack >' => 0])
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('shop_id', $parent)
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where(['deleted' => 0, 'lack >' => 0])
//                        ->where('shop_id', $parent)
//                        ->where('customer_id', $option['customer_id'])
//                        ->where('sell_date >=', $option['date_from'])
//                        ->where('sell_date <=', $option['date_to'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                } else {
//                    $total_orders = $this->db
//                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
//                        ->from('orders')
//                        ->where('shop_id', $parent)
//                        ->where(['deleted' => 0, 'lack >' => 0])
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->row_array();
//                    $data['_list_orders'] = $this->db
//                        ->from('orders')
//                        ->limit($config['per_page'], ($page - 1) * $config['per_page'])
//                        ->order_by('created', 'desc')
//                        ->where('shop_id', $parent)
//                        ->where(['deleted' => 0, 'lack >' => 0])
//                        ->where('customer_id', $option['customer_id'])
//                        ->where("(output_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
//                        ->get()
//                        ->result_array();
//                }
//            }
//        } else {
                if ($status == '0') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where('deleted', 0)
                            ->where('shop_id', $parent)
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('deleted', 0)
                            ->where('shop_id', $parent)
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where('deleted', 0)
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where('deleted', 0)
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '1') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where('deleted', 1)
                            ->where('shop_id', $parent)
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('deleted', 1)
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where('deleted', 1)
                            ->where('shop_id', $parent)
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('deleted', 1)
                            ->where('shop_id', $parent)
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '2') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('sell_time >=', $_GET['starts_date'])
                            ->where('sell_time <=', $_GET['ends_date'])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where("(output_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                }
                $output_data = array(
                    "error_code" => 0,
                    "data_orders" => $total_orders,
                    "list_orders" => $data['_list_orders']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $id = self::Getid();
                if ($id == 0) {

                    $output_data = array(
                        "error_code" => 400,
                        "message" => "Vui lòng đăng nhập lại"
                    );
                    echo json_encode($output_data, 200);
                    return;

                }
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);

                $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
                $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
                $user_paren = $this->db->from('users')->where('id', $parent)->limit(1)->get()->row_array();
                $shop_id = ($user_paren['group_id'] == 1) ? 1 : (($user_paren['parent_id'] == 1) ? $user_paren['id'] : $user_paren['parent_id']);
                $store = $this->db->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
                if (!empty($store)) {
                    $store_id = $store['ID'];
                } else {
                    $store_id = $id;
                }
                $order = $input_data;
                if ($order['sale_id'] == '') {
                    $order['sale_id'] = $id;
                }
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
                if (empty($order['sell_date'])) {
                    $order['sell_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                } else {
                    $order['sell_date'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $order['sell_date'])) + 7 * 3600);;
                }
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
                $this->db->select_max('output_code')->like('output_code', 'PX');
                $max_output_code = $this->db->where('shop_id', $shop_id)->get('orders')->row();
                $max_code = (int)(str_replace('PX', '', $max_output_code->output_code)) + 1;
                if ($max_code < 10)
                    $order['output_code'] = 'PX000000' . ($max_code);
                else if ($max_code < 100)
                    $order['output_code'] = 'PX00000' . ($max_code);
                else if ($max_code < 1000)
                    $order['output_code'] = 'PX0000' . ($max_code);
                else if ($max_code < 10000)
                    $order['output_code'] = 'PX000' . ($max_code);
                else if ($max_code < 100000)
                    $order['output_code'] = 'PX00' . ($max_code);
                else if ($max_code < 1000000)
                    $order['output_code'] = 'PX0' . ($max_code);
                else if ($max_code < 10000000)
                    $order['output_code'] = 'PX' . ($max_code);

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
//        }
        }
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
//        print_r($user);die();
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
            if ($user_2['id'] != $user['parent_id']) {
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
    public function customer($id_cus = '')
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
        if (!empty($id_cus)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $cus = $this->db->from('customers')->where('shop_id', $parent)->where('id', $id_cus)->get()->row_array();

                if (empty($cus)) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Khách hàng không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $data['_list_cus'] = $cus;
                    $data['customer_id'] = $id_cus;
                    $total_orders = $this->db
                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('orders')
                        ->where('deleted', 0)
                        ->where('lack', 0)
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->row_array();
                    $data['_list_orders'] = $this->db
                        ->from('orders')
                        ->order_by('created', 'desc')
                        ->where('deleted', 0)
                        ->where('lack', 0)
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->result_array();
                    $total_orders_lack = $this->db
                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('orders')
                        ->where(['deleted' => 0, 'lack >' => 0])
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->row_array();
                    $data['_list_orders_lack'] = $this->db
                        ->from('orders')
                        ->order_by('created', 'desc')
                        ->where(['deleted' => 0, 'lack >' => 0])
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->result_array();
                    $output_data = array(
                        "error_code" => 0,
                        "detail_customer" => $data['_list_cus'],
                        "order_cusomter" => $total_orders,
                        "list_order_detail" => $data['_list_orders'],
                        "order_cusomter_lack" => $total_orders_lack,
                        "list_order_detail_lack" => $data['_list_orders_lack']
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $name = $data['customer_name'];
                $email = $data['customer_email'];
                $phone = $data['customer_phone'];
                $date_birth = $data['customer_birthday'];
//                var_dump($check);die();
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
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $check_customer = $this->db->where('ID', $id_cus)->from('customers')->where('shop_id', $parent)->get()->row_array();
                if(empty($check_customer)){
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => " không tồn tại khách hàng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $data = $this->cms_common_string->allow_post($data, ['customer_name', 'customer_phone', 'customer_email', 'customer_addr', 'notes', 'customer_birthday', 'customer_gender']);
                $data['customer_birthday'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $data['customer_birthday'])) + 7 * 3600);
                $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_upd'] = $parent;
                $this->db->where('ID', $id_cus)->where('shop_id', $parent)->update('customers', $data);
                if ($this->db->affected_rows() > 0) {
                    $customer = $this->db->where('ID', $id_cus)->from('customers')->where('shop_id', $parent)->get()->row_array();
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $customer
                    ), 200);
                    return;
                } else {
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Đã xảy ra lỗi khi cập nhật, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
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

                if (!empty($_GET['status_customer']) || $_GET['status_customer'] == 0 ):
                    $status = $_GET['status_customer'];
                else:

                    $status = '';
                endif;

                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if ($status == 0) {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                } else if ($status == 1) {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                } else {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                }

                $output_data = array(
                    "error_code" => 0,
                    "count_customer" => $total_customer,
                    "list_customer" => $data['_list_customer']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);

                $name = $data['customer_name'];
                $email = $data['customer_email'];
                $phone = $data['customer_phone'];
                $date_birth = $data['customer_birthday'];
//                var_dump($check);die();
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
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

                $data = $this->cms_common_string->allow_post($data, ['customer_code', 'customer_name', 'customer_phone', 'customer_email', 'customer_addr', 'notes', 'customer_birthday', 'customer_gender']);
                $data['customer_birthday'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $data['customer_birthday'])) + 7 * 3600);
                $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_init'] = $parent;
                $data['shop_id'] = $parent;
                if ($data['customer_code'] == '') {
                    $this->db->select_max('customer_code')->like('customer_code', 'KH');
                    $max_customer_code = $this->db->get_where('customers', array('shop_id' => $parent))->row();
                    $max_code = (int)(str_replace('KH', '', $max_customer_code->customer_code)) + 1;
                    if ($max_code < 10)
                        $data['customer_code'] = 'KH00000' . ($max_code);
                    else if ($max_code < 100)
                        $data['customer_code'] = 'KH0000' . ($max_code);
                    else if ($max_code < 1000)
                        $data['customer_code'] = 'KH000' . ($max_code);
                    else if ($max_code < 10000)
                        $data['customer_code'] = 'KH00' . ($max_code);
                    else if ($max_code < 100000)
                        $data['customer_code'] = 'KH0' . ($max_code);
                    else if ($max_code < 1000000)
                        $data['customer_code'] = 'KH' . ($max_code);

                    $this->db->insert('customers', $data);
                    $id_custom = $this->db->insert_id();
                    $customer = $this->db->where('ID', $id_custom)->from('customers')->where('shop_id', $parent)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "detail_customer" => $customer
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $count = $this->db->where('customer_code', $data['customer_code'])->from('customers')->where('shop_id', $parent)->count_all_results();
                    if ($count > 0) {
                        echo json_encode(array(
                            "error_code" => 440,
                            "message" => "Mã khách hàng đã tồn tại, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    } else {
                        $this->db->insert('customers', $data);
                        $id_custom = $this->db->insert_id();
                        $customer = $this->db->where('ID', $id_custom)->from('customers')->where('shop_id', $parent)->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "detail_customer" => $customer,
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
            }
        }
    }
    public function supplier($id_sup = '')
    {
        $id = self::Getid();
//        print_r($id);die();
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
        if (!empty($id_sup)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $sup = $this->db->from('suppliers')->where('id', $id_sup)->where('shop_id', $parent)->get()->row_array();

                if (empty($sup)) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại Nhà cung cấp, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $output_data = array(
                        "error_code" => 0,
                        "detail_supplier" => $sup
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
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
                if (isset($_GET['status_supplier'])):
                    $status = $_GET['status_supplier'];
                else:
                    $status = '';
                endif;

                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if ($status == 0) {
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                } else if ($status == 1) {
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                } else {
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                }
                $output_data = array(
                    "error_code" => 0,
                    "count_supplier" => $total_supplier,
                    "list_supplier" => $data['_list_supplier']
                );
                echo json_encode($output_data, 200);
                return;
            }
        }
    }
    public function products($id_sp = '')
    {
        if (!empty($id_sp)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $product = $this->db->from('products')->where('shop_id', $parent)->where('ID', $id_sp)->get()->row_array();
                if (empty($product)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => "sản phẩm không tồn tại, vui lòng kiểm tra lại"
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data_products" => $product,
                );
                echo json_encode($output_data, 200);
                return;
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

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
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                if (empty($_GET['group_product_id'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['manufacture_id'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;
                $status = $_GET['status_products'];
                $group_product_id = $_GET['group_product_id'];
                $manufacture_id = $_GET['manufacture_id'];
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

                if ($status == '0') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()->result_array();
                        }
                    } else {
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
//                            print_r($this->db->last_query);die();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                } else if ($status == '1') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                } else if ($status == '2') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where('deleted', 1)
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where('deleted', 1)
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                }
                $output_data = array(
                    "error_code" => 0,
                    "count_products" => $total_prd,
                    "list_orders" => $data['data']['_list_product']
                );
                echo json_encode($output_data, 200);
                return;

            }

        }
    }
    public function group_products($id_gr =''){

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
        if(!empty($id_gr)){
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;

            }
            if($_SERVER['REQUEST_METHOD'] == 'GET'){
                $this->cms_nestedset->set('products_group');
                $config = $this->cms_common->cms_pagination_custom();
                $argc_s = $this->db->from('products_group')->where('ID',$id_gr)->where('shop_id', $parent)->get()->row_array();
                if(empty($argc_s)){
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại danh mục, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data" =>$argc_s
                );
                echo json_encode($output_data, 200);
                return;
            }
        }else{
            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;

            }
            if($_SERVER['REQUEST_METHOD'] == 'GET'){
                $this->cms_nestedset->set('products_group');
                $config = $this->cms_common->cms_pagination_custom();
                $total_prdGroup = $this->db->from('products_group')->where('shop_id', $parent)->count_all_results();
                $argc_s = $this->db->from('products_group')->where('shop_id', $parent)->get()->result_array();
                $output_data = array(
                    "error_code" => 0,
                    "count_group" => $total_prdGroup,
                    "list_group" =>$argc_s
                );
                echo json_encode($output_data, 200);
                return;

            }
        }
    }
    public function manufacture($id_manufacture =''){

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
        if(!empty($id_manufacture)){
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;

            }
            if($_SERVER['REQUEST_METHOD'] == 'GET'){

                $argc_s = $this->db->from('products_manufacture')->where('ID',$id_manufacture)->where('shop_id', $parent)->get()->row_array();
                if(empty($argc_s)){
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại danh mục, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data" =>$argc_s
                );
                echo json_encode($output_data, 200);
                return;
            }
        }else{
            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;

            }
            if($_SERVER['REQUEST_METHOD'] == 'GET'){
                $total_prdmanuf = $this->db->where('shop_id',$parent)->from('products_manufacture')->count_all_results();
                $argc_s = $this->db->from('products_manufacture')->where('shop_id', $parent)->get()->result_array();
                $output_data = array(
                    "error_code" => 0,
                    "count_group" => $total_prdmanuf,
                    "list_group" =>$argc_s
                );
                echo json_encode($output_data, 200);
                return;

            }
        }
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
            echo json_encode(array(
                "error_code" => 403,
                "message" => "Bạn không có quyền thực hiện chức năng này, vui lòng kiểm tra lại"
            ), 200);
            return;
        }
    }

}