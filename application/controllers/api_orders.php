<?php use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_orders extends CI_Controller
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
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;
                if ($status == '0') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where('orders.deleted', 0)
                            ->where('orders.shop_id', $parent)
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('orders.created', 'desc')
                            ->where('orders.deleted', 0)
                            ->where('orders.shop_id', $parent)
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where('orders.deleted', 0)
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where('orders.deleted', 0)
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '1') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where('orders.deleted', 1)
                            ->where('orders.shop_id', $parent)
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->order_by('orders.created', 'desc')
                            ->where('orders.deleted', 1)
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where('orders.deleted', 1)
                            ->where('orders.shop_id', $parent)
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('orders.created', 'desc')
                            ->where('orders.deleted', 1)
                            ->where('orders.shop_id', $parent)
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '2') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where(['orders.deleted' => 0, 'orders.lack >' => 0])
                            ->where('orders.shop_id', $parent)
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('orders.created', 'desc')
                            ->where('orders.shop_id', $parent)
                            ->where(['orders.deleted' => 0, 'orders.lack >' => 0])
                            ->where('orders.sell_time >=', $_GET['starts_date'])
                            ->where('orders.sell_time <=', $_GET['ends_date'])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->result_array();
                    } else {
                        $total_orders = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) total_debt')
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
                            ->where(['orders.deleted' => 0, 'orders.lack >' => 0])
                            ->where('orders.shop_id', $parent)
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
                            ->get()
                            ->row_array();
                        $data['_list_orders'] = $this->db
                            ->from('orders')
                            ->join('customers', 'orders.customer_id=customers.ID', 'LEFT')
//                    ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                            ->order_by('orders.created', 'desc')
                            ->where('orders.shop_id', $parent)
                            ->where(['orders.deleted' => 0, 'orders.lack >' => 0])
                            ->where("(orders.output_code LIKE '%" . $keyword . "%' or customers.customer_code LIKE '%" . $keyword . "%' OR customers.customer_name LIKE '%" . $keyword . "%' OR customers.customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->limit($limit, $offset)
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
                if (self::check_validate($order['sell_date'], 'date_order') === false) {
                    echo json_encode(array(
                        "error_code" => 114,
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

}