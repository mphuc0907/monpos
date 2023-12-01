<?php use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');



class api_products_group extends CI_Controller
{
    //nên copy
    private $auth;
//nên copy
    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }
    //nên copy
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
//nên copy
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
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT'){
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if (empty($data['prd_group_name'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $prd_group = $this->db->from('products_group')->where('id', $id_gr)->where('shop_id', $parent)->get()->row_array();

                if (empty($prd_group) && count($prd_group) == 0) {

                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại danh mục, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $prd_group_check = $this->db->from('products_group')->where(['parentid' => $prd_group['parentid'], 'prd_group_name' => $data['prd_group_name'],'shop_id' => $parent])->get()->row_array();

                if (empty($prd_group_check) || count($prd_group_check) == 0) {
                    $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $data['user_upd'] = $parent;
                    $this->db->where('ID', $id_gr)->where('shop_id', $parent)->update('products_group', $data);
                    $prd_group = $this->db->from('products_group')->where('id', $id_gr)->where('shop_id', $parent)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "message" =>'Cập nhật canh mục thành công',
                        "date_group" =>$prd_group

                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $output_data = array(
                        "error_code" => 500,
                        "message" =>'Cập nhật danh mục không thành công, tên danh mục đã tồn tại, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE'){
                $prd_group = $this->db->where('id', $id_gr)->where('shop_id', $parent)->from('products_group')->get()->row_array();
                if (isset($prd_group) && count($prd_group)) {
                    $countitem = $this->db->where('parentid', $prd_group['ID'])->from('products_group')->count_all_results();
                    $countprd = $this->db->where('prd_group_id', $prd_group['ID'])->from('products')->count_all_results();
                    if ($countitem > 0) {
                        $output_data = array(
                            "error_code" => 420,
                            "message" =>'Không thể xóa danh mục khi có danh mục cấp con.'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } elseif ($countprd > 0) {
                        $data['prd_group_id'] = 0;
                        $this->db->where('shop_id', $parent)->where('prd_group_id', $id_gr)->update('products', $data);
                        $this->db->delete('products_group', ['id' => $id_gr,'shop_id' => $parent]);
                        $output_data = array(
                            "error_code" => 0,
                            "message" =>'Xóa danh mục thành công!'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        $this->db->delete('products_group', ['id' => $id_gr,'shop_id' => $parent]);
                        $output_data = array(
                            "error_code" => 0,
                            "message" =>'Xóa danh mục thành công!'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }else{
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại Danh mục, vui lòng kiểm tra lại.'
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

            }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $data['level'] = 0;
                if (empty($data['prd_group_name'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (isset($data['parentid']) && $data['parentid'] > 0) {
                    $level = $this->db->select('level')->from('products_group')->where('shop_id', $parent)->where('ID', $data['parentid'])->limit(1)->get()->row_array();
                    $data['level'] = $level['level'] + 1;
                    $prd_group = $this->db->from('products_group')->where('shop_id', $parent)->where(['parentid' => $data['parentid'], 'prd_group_name' => $data['prd_group_name']])->get()->row_array();
                } else {

                    $prd_group = $this->db->from('products_group')->where(['shop_id' => $parent,'parentid' => '-1', 'prd_group_name' => $data['prd_group_name']])->get()->row_array();
                }
                if (!empty($prd_group) && count($prd_group)) {
                    $output_data = array(
                        "error_code" => 440,
                        "message" => 'Đã tồn tại Danh mục, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $data['user_init'] = $parent;
                    $data['shop_id'] = $parent;
                    $this->db->insert('products_group', $data);
                    $prd_group_2 = $this->db->from('products_group')->where('shop_id', $parent)->where('prd_group_name',$data['prd_group_name'])->get()->row_array();
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