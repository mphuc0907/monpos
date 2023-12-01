<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_card_services_history_used extends CI_Controller
{
    // Phần nên copy vào
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
    //Kết thúc phần copy

    //Phần xử lý các trường hợp trong lịch sử sử dụng thẻ dịch vụ

    public function card_services_history_used($id_history_card = '') {
        // Check ID có đc truyền vào hay không
        if (!empty($id_history_card)) {
            //Check phương truyền vào
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo json_encode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
            }
            $id_custom = "";
            if (isset($_GET['id_custom'])) {
                $id_custom = $_GET['customerID'] ;
            }
            //Check ID xem quá thời gian đăng nhập chưa và yêu cầu đăng nhập lại để láy token mới
            $id = self::Getid();
            if ($id == 0){
                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }
            //kiểm tra user đang đang nhập
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            // Kiểm tra phương thức GET
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $card_services_history = $this->db->from('card_services_history_used')->where('ID', $id_history_card)->where('deleted', 0)->where('shopID', $parent)->get()->row_array();
//                print_r($card_services_history);die();
//                dd($cart_services);
                if (empty($card_services_history)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => "Chưa có lịch sử dụng thẻ dịch vụ, vui lòng kiểm tra lại"
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
//                print_r($card_services_history);die();
                $output_data = array(
                    "error_code" => 0,
                    "data_card_services_history" => $card_services_history,
                );
                echo json_encode($output_data, 200);
                return;
                }

            // Sửa lịch sử sửa dụng thẻ
            elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $data = $this->cms_common_string->allow_post($data, ['cart_services_ID', 'time_used', 'productID', 'customerID', 'shopID']);
                $this->db->where('ID', $id_history_card)->update('card_services', $data);
                $card_services_history =$this->db->from('card_services_history_used')->where('shopID', $parent)->where('ID', $id_history_card)->get()->row_array();
                $output_data = array(
                    "error_code" => 0,
                    "data_card_services_history" => $card_services_history,
                );
                echo json_encode($output_data, 200);
                return;
            }
            // end chức năng sửa lịch sử

            //Chức năng xóa thẻ
            elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
                $card_services_history = $this->db->from('card_services_history_used')->where('shopID', $parent)->where('ID', $id_history_card)->get()->row_array();
                if (!empty($card_services_history) && count($card_services_history)) {
                    $this->db->where('ID', $id_history_card)->update('card_services_history_used', ['deleted' => 1]);

                    $output_data = array(
                        "error_code" => 0,
                        "message" => 'Xóa lịch sử sửa dụng thẻ dịch vụ thành công',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }else {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => 'Không tìm thấy lịch sửa dụng thẻ dịch vụ vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }
            //
        }else {
            if ($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            $cart_services_ID = "";
            if (isset($_GET['cart_services_ID'])) {
                $cart_services_ID = $_GET['cart_services_ID'] ;
            }
            $customerID = "";
            if (isset($_GET['customerID'])) {
                $customerID = $_GET['customerID'] ;
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
            if (!empty($_GET['ends_date'])) :
                $ends_date = $_GET['ends_date'];
            else :
                $ends_date = "";
            endif;
            if (!empty($_GET['starts_date'])) :
                $starts_date = $_GET['starts_date'];
                // $starts_date->format('Y-m-d H:i:s');
                // print_r($starts_date);die;
            else :
                $starts_date = "";
            endif;
            if (!empty($_GET['keyword'])):
                $keyword = $_GET['keyword'];
            else:
                $keyword = '';
            endif;
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == "GET") {

                if (!empty($customerID)) {
                    $condition['customerID'] = $customerID;
                }

                if (!empty($cart_services_ID)) {
                    $condition['cart_services_ID'] = $cart_services_ID;
                }

                if (!empty($starts_date) && !empty($ends_date)) {
                    $condition['time_created >='] = $starts_date;
                    $condition['time_created <='] = $ends_date;
                }

                $condition['deleted'] = 0;
                $condition['shopID'] = $parent;

                $card_services_history = $this->db
                    ->from('card_services_history_used')
                    ->where($condition)
                    ->get()
                    ->result_array();

                if (empty($card_services_history)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => "Không có lịch sử sử dụng thẻ dịch vụ, vui lòng kiểm tra lại!"
                    );
                } else {
                    $output_data = array(
                        "error_code" => 0,
                        "data_card_services_history" => $card_services_history,
                    );
                }

                echo json_encode($output_data, 200);
                return;
             }
        }
    }
    //Kết thúc phần xử lý
}