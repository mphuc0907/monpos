<table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th class="text-center views-phieuchi-index_php">Mã phiếu chi</th>
                    <th class="text-center hidden">Kho chi</th>
                    <th class="text-center">Ngày chi</th>
                    <th class="text-center">Người chi</th>
                    <th class="text-center">Ghi chú</th>
                    <th class="text-center">Hình thức chi</th>
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
                                    # code...
                                    break;
                                case '1':
                                    $tenhinhthuc = 'Chi mua hàng';
                                    break;
                                case '2':
                                    $tenhinhthuc = 'Chi nhân viên';
                                    break;
                                case '3':
                                    $tenhinhthuc = 'Chi cố định';
                                    break;
                                case '4':
                                    $tenhinhthuc = 'Chi khách hàng';
                                    break;
                                default:
                                    $tenhinhthuc = 'Chi khác';
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
                                onclick="cms_delThuchi(<?php echo $item['ID']; ?>, 1);"></i>
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

