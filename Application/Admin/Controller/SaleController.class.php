<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 销售统计
 */
class SaleController extends CommonController
{
    function __construct(){
        parent::__construct();
    }
    public function index(){
        if(IS_AJAX){
            $user = $this->user();
            $data = M()->query("SELECT 
                    m.d as date,m.c as count,m1.c as gz,m.paid_fee,m2.c as cz_c,m2.total_recharge as cz_fee 
                FROM (
                    SELECT DATE_FORMAT(created, '%Y-%m-%d') AS d, count(*) AS c, SUM(paid_fee) AS paid_fee FROM mall_trade
                    WHERE `status` IN ( 'tosend', 'send', 'sendpart', 'signed', 'success', 'toout' )
                    AND created > DATE_FORMAT( FROM_UNIXTIME( UNIX_TIMESTAMP(now()) - 86400 * 9 ), '%Y-%m-%d' )
                    GROUP BY DATE_FORMAT(created, '%Y-%m-%d')
                    ORDER BY d DESC
                ) AS m LEFT JOIN (
                    SELECT DATE_FORMAT(FROM_UNIXTIME(subscribe_time),'%Y-%m-%d') as d,count(*) as c from wx_user 
                    WHERE subscribe=1 and subscribe_time BETWEEN (UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(now(),'%Y-%m-%d'),' 00:00:00')) - 9*86400)
                        AND (UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(now(),'%Y-%m-%d'),' 23:59:59'))) 
                    group by DATE_FORMAT(FROM_UNIXTIME(subscribe_time),'%Y-%m-%d') order BY d DESC
                ) as m1 on m.d=m1.d LEFT JOIN (
                    select DATE_FORMAT(created,'%Y-%m-%d') as d,count(*) as c, SUM(once_amount) AS total_recharge from member_recharge where `status` = 'success'
                    AND created > DATE_FORMAT( FROM_UNIXTIME( UNIX_TIMESTAMP(now()) - 86400 * 9 ), '%Y-%m-%d' ) group by DATE_FORMAT(created,'%Y-%m-%d')
                ) as m2 on m.d=m2.d");
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    public function goods(){
        $date = strtotime(I('date'));
        # 非预定类型输入与默认均查看昨天
        if ( $date <= 0 )
        {
            $date = time()-86400;
        }
        $this->assign('date', date('Y-m-d', $date));
        if(IS_AJAX){
            $data = $this->forecast($date);
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    private function forecast ( $date )
    {
        $user = $this->user();
        $start = date('Y-m-d', $date-3*86400).' 00:00:00';
        $end = date('Y-m-d', $date).' 23:59:59';
        $data = M()->query("SELECT
                created,title,sku,outer_id,avg(sum) as avg,
                IF (sum - avg(sum) > 0, '1', '2') AS trend,
                sum,
                IF (
                    sum - avg(sum) > 0,
                    sum * 1.1,
                    sum * 0.9
                ) AS forecast
            FROM
            (
                SELECT
                    g.created AS created,
                    product_id AS id,
                    p.sku_json AS sku,
                    DATE_FORMAT(t.created, '%Y-%m-%d') AS d,
                    o.title AS title,
                    p.outer_id AS outer_id,
                    sum(o.num) AS sum
                FROM
                    mall_order AS o
                LEFT JOIN mall_trade AS t ON o.tid = t.tid
                LEFT JOIN mall_product AS p ON o.product_id = p.id
                LEFT JOIN mall_goods AS g ON o.goods_id = g.id
                WHERE
                    t.created > '{$start}'
                AND t.created < '{$end}'
                AND t.seller_id = '{$user['shop_id']}'
                GROUP BY
                    product_id,
                    DATE_FORMAT(t.created, '%Y-%m-%d')
                ORDER BY
                    id ASC,
                    d DESC
            ) AS t
            GROUP BY id ORDER BY sum DESC");
        $GoodsModel = D('Goods');
        foreach( $data as $key => $value )
        {
            $data[$key]['sku'] = get_spec_name($value['sku']);
            # 计算销售趋势，推荐销量
            if ( $value['sum'] - $value['avg'] == 0 ) {
                $data[$key]['trend'] = 0;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }elseif ( $value['sum'] - $value['avg'] > 0 ) {
                $data[$key]['trend'] = 1;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }else{
                $data[$key]['trend'] = -1;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }
        }
        return $data;
    }
    
    // 预计销量函数 param: 当日实际数，销售趋势
    private function get_forecast ( $num, $trend )
    {
        $result = 0;
        switch ( $trend )
        {
            case -1:
                $result = $num*0.9;
            break;
            case 1:
                $result = $num*1.1;
            break;
            default:
                $result = $num;
            break;
        }
        return $result;
    }
    
    /**
     * 产品销量导出
     */
    public function exportSale(){
        $date = strtotime(I('date'));
        # 非预定类型输入与默认均查看昨天
        if ( $date <= 0 )
        {
            $date = time()-86400;
        }
        $products = $this->forecast($date);
        
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
    
        // 设置文档基本属性
        $objPHPExcel->getProperties()
        ->setCreator("微通联盟")
        ->setLastModifiedBy("微通联盟")
        ->setTitle(date('Y-m-d H:i:s'));
        //->setDescription(json_encode($_POST));
    
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle(date('Y-m-d', $date).'简易预计次日出货量');
    
        $i=1;   // 单元格写入开始行
        // 设置标题
        $worksheet
        ->setCellValue('A'.$i, '品名')
        ->setCellValue('B'.$i, '规格')
        ->setCellValue('C'.$i, '创建时间')
        ->setCellValue('D'.$i, '商家编号')
        ->setCellValue('E'.$i, '前三天平均成交量')
        ->setCellValue('F'.$i, '成交趋势')
        ->setCellValue('G'.$i, '当天成交量')
        ->setCellValue('H'.$i, '预计次日出货量');
    
        foreach($products as $k=>$v){
            $i++;
            switch ( $v['trend'] )
            {
                case -1:
                    $v['trend'] = '降';
                break;
                case 1:
                    $v['trend'] = '升';
                break;
                default:
                    $v['trend'] = '平';
                break;
            }
            $worksheet
            ->setCellValueExplicit('A'.$i, $v['title'])
            ->setCellValueExplicit('B'.$i, $v['sku'])
            ->setCellValueExplicit('C'.$i, $v['created'])
            ->setCellValueExplicit('D'.$i, $v['outer_id'])
            ->setCellValueExplicit('E'.$i, $v['avg'])
            ->setCellValueExplicit('F'.$i, $v['trend'])
            ->setCellValueExplicit('G'.$i, $v['sum'])
            ->setCellValueExplicit('H'.$i, $v['forecast']);
        }
    
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.date('Y-m-d', $date).'简易预计次日出货量.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
    
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
    
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }
    public function shopStat ()
    {
        if(!IS_AJAX){
            $start_date = I('get.start_date', date('Y-m-d 00:00:00'));//下单时间  开始
            $end_date = I('get.end_date', date('Y-m-d 23:59:59'));//下单时间  结束
            $this->assign(array(
                'start_date'  =>$start_date,
                'end_date'    =>$end_date
            ));
            $this->display();
        }
        
        $start = $_GET['start_date'];
        $end = $_GET['end_date'];
        $ti = substr($start, 0, 10);
        $time = str_replace('-','',$ti).'00000';
        
        $sql = "SELECT m.*,m1.*
                FROM (
                    SELECT
                        mall_trade.seller_id AS id,LEFT(mall_trade.pay_time, 10) AS `支付日期`,mall_trade.seller_nick AS `nick`,
                        COUNT(mall_trade.tid) AS `count`,SUM(mall_trade.total_fee) AS `total_fee`, SUM(mall_trade.post_fee) AS `post_fee`,
                        SUM(mall_trade.paid_balance) AS `paid_balance`,-SUM(mall_trade.paid_no_balance) AS `paid_no_balance`, -SUM(mall_trade.discount_fee) AS `discount_fee`,
                        SUM(mall_trade.payment) AS `payment`,-SUM(ROUND(payment*0.006,2)) as `wechat_fee`, -SUM((SELECT SUM(total_fee) FROM mall_trade_difference WHERE tid=mall_trade.tid)) AS `trade_difference`,
                        -SUM(mall_trade.total_cost) AS total_cost,COUNT(DISTINCT mall_trade.buyer_id) AS `buyer_id`,SUM(mall_trade.total_num) AS `total_num`
                    FROM
                    mall_trade
                    WHERE
                    mall_trade.tid > '{$time}' AND mall_trade.pay_time BETWEEN '{$start}' AND '{$end}'
                    GROUP BY mall_trade.seller_id
                    ORDER BY `count` DESC, `total_fee` DESC, `post_fee` DESC
                ) AS m LEFT JOIN (
                    SELECT
                        t.seller_id AS id,t.seller_nick,sum(r.refund_fee)+sum(r.refund_post) AS `refund_fee`,sum(r.refund_num) AS `refund_num`
                    FROM
                    mall_trade_refund AS r
                    LEFT JOIN mall_order AS o ON o.oid=r.refund_id
                    LEFT JOIN mall_trade AS t ON t.tid=o.tid
                    WHERE
                    r.refund_modify BETWEEN '{$start}'AND '{$end}'AND r.refund_state = '3'
                    GROUP BY t.seller_id
                ) as m1 on m.id=m1.id";
        $data = M()->query($sql);
        
        $this->ajaxReturn($data);
    }
}
?>