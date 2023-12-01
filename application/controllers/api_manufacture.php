<?php
use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');


class api_manufacture extends CI_Controller
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
        if (empty($user)){
            return 0;
        }else {
            return $user['id'];
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
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT'){
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if (empty($data['prd_manuf_name'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $prd_group = $this->db->from('products_manufacture')->where('id', $id_manufacture)->where('shop_id', $parent)->get()->row_array();

                if (empty($prd_group) && count($prd_group) == 0) {

                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại Nhà sản xuất, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $prd_group_check = $this->db->from('products_manufacture')->where([ 'prd_manuf_name' => $data['prd_manuf_name'],'shop_id' => $parent])->get()->row_array();

                if (empty($prd_group_check) || count($prd_group_check) == 0) {
                    $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $data['user_upd'] = $parent;
                    $this->db->where('ID', $id_manufacture)->where('shop_id', $parent)->update('products_manufacture', $data);
                    $prd_group = $this->db->from('products_manufacture')->where('id', $id_manufacture)->where('shop_id', $parent)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "message" =>'Cập nhật Nhà sản xuất thành công',
                        "date_group" =>$prd_group

                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $output_data = array(
                        "error_code" => 500,
                        "message" =>'Cập nhật Nhà sản xuất không thành công, tên danh mục đã tồn tại, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE'){
                $prd_group = $this->db->where('id', $id_manufacture)->where('shop_id', $parent)->from('products_manufacture')->get()->row_array();
                if (isset($prd_group) && count($prd_group)) {
                    $countprd = $this->db->where('prd_manufacture_id', $prd_group['ID'])->from('products')->count_all_results();
                    if ($countprd > 0) {
                        $data['prd_group_id'] = 0;
                        $this->db->where('shop_id', $parent)->where('prd_manufacture_id', $id_manufacture)->update('products', $data);
                        $this->db->delete('products_manufacture', ['id' => $id_manufacture,'shop_id' => $parent]);
                        $output_data = array(
                            "error_code" => 0,
                            "message" =>'Xóa Nhà sản xuất thành công!'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        $this->db->delete('products_manufacture', ['id' => $id_manufacture,'shop_id' => $parent]);
                        $output_data = array(
                            "error_code" => 0,
                            "message" =>'Xóa Nhà sản xuất thành công!'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }else{
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại Nhà sản xuất, vui lòng kiểm tra lại.'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
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

            }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if (empty($data['prd_manuf_name'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $prd_manuf = $this->db->from('products_manufacture')->where('prd_manuf_name', $data['prd_manuf_name'])->where('shop_id',$parent)->get()->row_array();
                if (!empty($prd_manuf) && count($prd_manuf)) {
                    $output_data = array(
                        "error_code" => 440,
                        "message" => 'Đã tồn tại Nhà sản xuất, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $data['user_init'] = $parent;
                    $data['shop_id'] = $parent;
                    $this->db->insert('products_manufacture', $data);
                    $prd_group_2 = $this->db->from('products_manufacture')->where('shop_id', $parent)->where('prd_manuf_name',$data['prd_manuf_name'])->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "data_prd_group" => $prd_group_2,
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }
        }
    }
}