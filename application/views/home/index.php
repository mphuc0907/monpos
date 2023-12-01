<!--<div class="row">-->
<!--    <div class="col-md-12">-->
<!--        <div class="alert alert-success text-center text-capitalize" role="alert">Chào mừng bạn đến với hệ thông quản lý bán hàng doanh nghiệp tư nhân kim khí Quang Na</div>-->
<!--    </div>-->
<!--</div>-->

<div class="row">
    <div class="report">
        <div class="col-md-12" >
            <h4 class="dashboard-title">Hoạt động hôm nay</h4>
        </div>
        <div class="col-md-4 ">
            <div class="report-box box-35149A " style="position: inherit" >
                <div class="infobox-icon col-12 ">
                    <img src="public\templates\images\Group 4.svg" alt="">
                </div>
                <div class="infobox-data col-12">
                    <h3 class="infobox-title">Số tiền bán hàng</h3>
                    <span
                            class="infobox-data-number text-center"><?php echo (isset($tongtien)) ? cms_encode_currency_format($tongtien) : '0'; ?> đ</span>
                </div>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">Hoạt động</h3>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="info col-xs-7">Tiền bán hàng</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($tongtien)) ? cms_encode_currency_format($tongtien) : '0'; ?></div>
                        <div class="info col-xs-7">Số đơn hàng</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($slsorders)) ? cms_encode_currency_format($slsorders) : '0'; ?></div>
                        <div class="info col-xs-7">Số sản phẩm</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($slsitem)) ? cms_encode_currency_format($slsitem) : '0'; ?></div>
                        <div class="info col-xs-7">Khách hàng trả</div>
                        <div class="info col-xs-5 data text-right">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-box box-orange" style="position: inherit">
                <div class="infobox-icon col-12 ">
                    <img src="public\templates\images\shop_d.svg" alt="">
                </div>
                <div class="infobox-data col-12 ">
                    <h3 class="infobox-title">Số đơn hàng:</h3>
                    <span
                            class="infobox-data-number text-center"><?php echo (isset($slsorders)) ? cms_encode_currency_format($slsorders) : '0'; ?></span>


                </div>
            </div>
            <div class="widget ">
                <div class="widget-header">
                    <h3 class="widget-title">Thông tin kho</h3>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="info col-xs-7">Tồn kho</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($slsinventory)) ? cms_encode_currency_format($slsinventory) : '0'; ?></div>
                        <div class="info col-xs-7">Hết Hàng</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($slsaceitem)) ? cms_encode_currency_format($slsaceitem) : '0'; ?></div>
                        <div class="info col-xs-7">Sắp hết hàng</div>
                        <div class="info col-xs-5 data text-right">0</div>
                        <div class="info col-xs-7">Vượt định mức</div>
                        <div class="info col-xs-5 data text-right">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-box box-green" style="position: inherit">
                <div class="infobox-icon col-12 ">
                    <img src="public\templates\images\box_sp.svg" alt="">
                </div>
                <div class="infobox-data col-12 ">
                    <h3 class="infobox-title">Số sản phẩm:</h3>
                    <span
                            class="infobox-data-number text-center"><?php echo (isset($slsitem)) ? cms_encode_currency_format($slsitem) : '0'; ?></span>
                </div>
            </div>
            <div class="widget ">
                <div class="widget-header">
                    <h3 class="widget-title">Thông tin sản phẩm</h3>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="info col-xs-7">sản phẩm/Nhà sản xuất</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($data['_sl_product'])) ? $data['_sl_product'] : 0; ?>
                            /<?php echo (isset($data['_sl_manufacture'])) ? $data['_sl_manufacture'] : 0; ?></div>
                        <div class="info col-xs-7">Chưa làm giá bán</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($lamgiaban)) ? cms_encode_currency_format($lamgiaban) : '0'; ?></div>
                        <div class="info col-xs-7">Chưa nhập giá mua</div>
                        <div
                                class="info col-xs-5 data text-right"><?php echo (isset($lamgiamua)) ? cms_encode_currency_format($lamgiamua) : '0'; ?></div>
                        <div class="info col-xs-7">Hàng chưa phân loại</div>
                        <div class="info col-xs-5 data text-right">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row hidden" style="margin: 20px 0; overflow: hidden; ">
    <div class="chart-report">
        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-signal"></i>Biểu đồ doanh số tuần</div>
                    <div class="panel-body">
                        Đang xây dựng - coming soon
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-cloud"></i>Thông tin từ web</div>
                    <div class="panel-body">
                        Đang xây dựng - coming soon
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-rss"></i>Tin chuyên ngành</div>
                    <div class="panel-body">
                        Đang xây dựng - coming soon
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>