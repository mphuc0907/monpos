<div class="orders">
    <div class="breadcrumbs-fixed panel-action">
        <div class="row">
            <div class="orders-act">
                <div class="col-md-4 col-md-offset-2">
                    <div class="left-action text-left clearfix">
                        <h2>Danh sách phiếu thu</h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="right-action text-right">
                        <div class="btn-groups">
                             <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#create-phieuchithu"><i class="fa fa-plus"></i> Tạo Mới
                            </button>                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="main-space orders-space"></div>
    <div class="orders-content">
        <div class="product-sear panel-sear">
            <div class="form-group col-md-3 padd-0">
                <input type="text" class="form-control" id="order-search"
                       placeholder="Nhập mã phiếu tìm kiếm">
            </div>
            <div class="form-group col-md-9 padd-0" style="padding-left: 5px;">
                <div class=" padd-0">
                    <div class="col-md-4 padd-0">
                        <select id="search-option-1" class="form-control">
                            <option value="-1">-- Hình thức --</option>
                            <option value="0">Thu bán hàng</option>
                            <option value="1">Thu nhân viên</option>
                            <option value="2">Thu sửa chữa</option>
                            <option value="3">Thu dịch vụ</option>                            
                            <option value="4">Thu khác</option>
                        </select>
                    </div>
                    <div class="col-md-5 padd-0" style="padding-left: 5px;">
                        <div class="input-daterange input-group" id="datepicker">
                            <input type="text" class="input-sm form-control" id="search-date-from" placeholder="Từ ngày"
                                   name="start"/>

                            <span class="input-group-addon">to</span>
                            <input type="text" class="input-sm form-control" id="search-date-to" placeholder="Đến ngày"
                                   name="end"/>
                        </div>
                    </div>
                    <div class="col-md-3 padd-0" style="padding-left: 5px;">
                        <button style="box-shadow: none;" type="button" class="btn btn-primary btn-large"
                                onclick="cms_paging_listthuchithu(1)"><i class="fa fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 padd-0 content">
            <div class="btn-group order-btn-calendar">
                <button type="button" onclick="cms_order_week()" class="btn btn-default">Tuần</button>
                <button type="button" onclick="cms_order_month()" class="btn btn-default">Tháng</button>
                <button type="button" onclick="cms_order_quarter()" class="btn btn-default">Quý</button>
            </div>
        </div>
        <div class="phieuchithu-main-body">

            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th class="text-center views-phieuchi-index_php">Mã phiếu thu</th>
                    <th class="text-center hidden">Kho thu</th>
                    <th class="text-center">Ngày thu</th>
                    <th class="text-center">Người thu</th>
                    <th class="text-center">Ghi chú</th>
                    <th class="text-center">Hình thức thu</th>
                    <th class="text-center" style="background-color: #fff;">Số tiền</th>                    
                    <th></th>
                </tr>
                </thead>
                <tbody class="ajax-loadlist-phieuchi">
                <?php if (isset($_list_thuchi) && count($_list_thuchi)) :
                    foreach ($_list_thuchi as $key => $item) :
                        ?>
                    <tr id="tr-item-<?php echo $item['ID']; ?>">
                         <td class="text-center">
                            <?php echo (!empty($item['thuchi_code'])) ? $item['thuchi_code'] : '-'; ?>
                        </td>
                        <td class="text-center hidden">
                            <?php echo (!empty($item['store_id'])) ? $item['store_id'] : '-'; ?>
                        </td>
                        
                        <td class="text-center"><?php echo ($item['thuchi_date'] != '0000-00-00 00:00:00') ? gmdate("H:i d/m/Y", strtotime(str_replace('-', '/', $item['thuchi_date'])) + 7 * 3600) : '-'; ?></td>
                        
                        <td class="text-center"><?php echo cms_getNameAuthbyID($item['user_init']); ?></td>

                        <td class="text-center">
                            <?php echo (!empty($item['notes'])) ? $item['notes'] : '-'; ?>
                        </td>
                        <td class="text-center">
                            <?php 

                            $hinhthucid = $item['hinhthuc']; 
                            $tenhinhthuc = '';

                            switch ($hinhthucid) {
                                case '0':
                                    $tenhinhthuc = 'Thu bán hàng';
                                    break;
                                case '1':
                                    $tenhinhthuc = 'Thu nhân viên';
                                    break;
                                case '2':
                                    $tenhinhthuc = 'Thu sửa chữa';
                                    break;
                                case '3':
                                    $tenhinhthuc = 'Thu dịch vụ';
                                    break;                                
                                default:
                                    $tenhinhthuc = 'Thu khác';
                                    break;
                            }

                            echo $tenhinhthuc;

                        ?>
                        </td>
                        <td class="text-center">
                            <?php echo (!empty($item['tongtien'])) ? $item['tongtien'] : '-'; ?>
                        </td>

                        <td class="text-center">
                            <i class="fa fa-trash-o" style="cursor:pointer;" 
                                onclick="cms_delThuchiThu(<?php echo $item['ID']; ?>, 1);"></i>
                        </td>

                    </tr>
                    <?php
                    endforeach;
                else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="alert alert-info summany-info clearfix" role="alert">
               <div class="ajax-loadlist-total sm-info pull-left padd-0">
                Số phiếu chi:
                    <span><?php echo (isset($_total_thuchi) && !empty($_total_thuchi)) ? $_total_thuchi : '0'; ?></span>
                    Tổng tiền: <span><?php echo (isset($total_money) && !empty($total_money)) ? cms_encode_currency_format($total_money) : '0'; ?> đ</span> 
                    
                </div>
                <div class="pull-right">
                    <?php echo $_pagination_link; ?>
                </div>
            </div>
        </div><!-- end .phieuchi-main-body-->

        <div class="phieuchi-body"></div>

    </div>
</div>