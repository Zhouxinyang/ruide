<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 退款
 * @author 兰学宝
 */
class RefundController extends CommonController
{
    private $model = null;
    public $authRelation = array(
        'now'      => 'agree',
        'refuse'   => 'agree',
        'add'      => 'agree',
        'detail'   => 'index'
    );
    
    private function refundModel(){
        if(is_null($this->model)){
            // 判断是否具有全部店铺的权限
            $sellerId = \Common\Common\Auth::get()->validated('admin','shop','all');
            if($sellerId === false){
                $sellerId = $this->user('shop_id');
            }
            $this->model = new \Admin\Model\RefundModel($sellerId);
        }
        return $this->model;
    }
    
    /**
     * 列表
     */
    public function index(){
        $Model = $this->refundModel();
        $date1 = date_create($_GET['start_date']);
        $date2 = date_create($_GET['end_date']);
        $diff = date_diff($date1,$date2);
        $date = str_replace('+','',$diff->format("%R%a"));
        if($date>31){
            $this->error('只能查询一个月以内的');
        }
        
        if(!IS_AJAX){
            // 判断是否具有全部店铺的权限
            $accessAllShop = \Common\Common\Auth::get()->validated('admin','shop','all');
            $allShop = $this->shops();
            $this->assign(array(
                'start_date' => date('Y-m-d 00:00:00', strtotime('-30 day')),
                'end_date'   => date('Y-m-d 23:59:59'),
                'allShop'    => $allShop,
            ));
            $this->display();
        }
        $data = $Model->getAll($paging=0);
        $this->ajaxReturn($data);
    }
    
    /**
     * 导出
     */
    public function export(){
        $Model = $this->refundModel();
        $data = $Model->getAll($paging=1);
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('售后退款');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        // 退款状态、原因
        $Static = new \Common\Model\StaticModel();
        $allState = $Static->refundedState();
        
        foreach($data['rows'] as $key => $item){
           $data['rows'][$key]['refund_state'] = $allState[$item['refund_state']];
        }
        $searchStr = "查询时间段：".$_GET["start_date"]."至".$_GET["end_date"];
        if(is_numeric($_GET["tid"])){
            $searchStr.= "\r\n订单号：".$_GET["tid"];
        }
        if(is_numeric($_GET["refund_state"])){
            $searchStr.= "\r\n退款状态：".$data['rows'][$key]['refund_state'];
        }
        
        $worksheet->setCellValueExplicit('A1', $searchStr);
        $worksheet->getRowDimension(1)->setRowHeight(40);
         
        $i=2;  
        $worksheet
        ->setCellValue('A'.$i, '订单号')
        ->setCellValue('B'.$i, '申请时间')
        ->setCellValue('C'.$i, '卖家')
        ->setCellValue('D'.$i, '商品ID')
        ->setCellValue('E'.$i, '商品名称')
        ->setCellValue('F'.$i, '退款数量')
        ->setCellValue('G'.$i, '退款金额')
        ->setCellValue('H'.$i, '运费补偿')
        ->setCellValue('I'.$i, '快递单号')
        ->setCellValue('J'.$i, '退款原因')
        ->setCellValue('K'.$i, '退款状态')
        ->setCellValue('L'.$i, '买家ID');
    
        
        $sum = 0;
        foreach($data['rows'] as $k=>$v){
            $i++;
            $worksheet
            ->setCellValueExplicit('A'.$i, $v['tid'])
            ->setCellValueExplicit('B'.$i, $v['refund_created'])
            ->setCellValueExplicit('C'.$i, $v['seller_nick'])
            ->setCellValueExplicit('D'.$i, $v['goods_id'])
            ->setCellValueExplicit('E'.$i, $v['title']."  ".$v['spec'])
            ->setCellValueExplicit('F'.$i, $v['refund_num'])
            ->setCellValueExplicit('G'.$i, $v['refund_fee'])
            ->setCellValueExplicit('H'.$i, $v['refund_post'])
            ->setCellValueExplicit('I'.$i, $v['refund_express'])
            ->setCellValueExplicit('J'.$i, $v['refund_reason_str'])
            ->setCellValueExplicit('K'.$i, $v['refund_state'])
            ->setCellValueExplicit('L'.$i, $v['buyer_id']);
           
        }
        
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $text = iconv('UTF-8', 'GB2312', '售后退款');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }
    
    /**
     * 退款详情
     */
    public function detail(){
        $Model = $this->refundModel();
        $data = $Model->getDetail($_REQUEST['tid']);
        if(empty($data)){
            $this->error($Model->getError());
        }
        
        $this->assign(array(
            'trade'  => $data['trade'],
            'reason' => $data['reason']
        ));
        $this->display();
    }
    
    /**
     * 拒绝退款
     */
    public function refuse(){
        $Model = $this->refundModel();
        $result = $Model->refuse(array('refund_id' => $_POST['refund_id'], 'refund_sremark' => $_POST['refund_sremark']));
        if($result <= 0){
            $this->error($Model->getError());
        }
        $this->success();
    }
    
    /**
     * 同意退款
     */
    public function agree(){
        $data = array(
            'refund_id'         => $_POST['refund_id'],
            'refund_fee'        => $_POST['refund_fee'] * 1,
            'refund_post'       => $_POST['refund_post'] * 1,
            'refund_num'        => $_POST['refund_num'] * 1,
            'refund_reason'     => $_POST['refund_reason']
        );
        
        $address = null;
        if(!empty($_POST['receiver_address'])){
            $address = array(
                'receiver_name'     => $_POST['receiver_name'],
                'receiver_mobile'   => $_POST['receiver_mobile'],
                'receiver_address'  => $_POST['receiver_address']
            );
        }
        
        $Model = $this->refundModel();
        $result = $Model->agree($data, $address);
        if($result > 0){
            $this->success('已保存');
        }
        $this->error($Model->getError());
    }
    
    /**
     * 立即退款
     */
    public function now(){
        $data = array(
            'refund_fee'    => $_POST['refund_fee'] * 1,
            'refund_post'   => $_POST['refund_post'] * 1);
        
        $Model = $this->refundModel();
        $refund = $Model->getRefundById($_POST['refund_id']);
        if(empty($refund)){
            $this->error($Model->getError());
        }
        
        $result = $Model->refundMoney($refund, $data);
        if($result > 0){
            $this->success();
        }
        $this->error($Model->getError());
    }
    
    public function add(){
        $data = array(
            'refund_id'         => $_POST['refund_id'],
            'refund_num'        => $_POST['refund_num'],
            'refund_reason'     => $_POST['refund_reason'],
            'refund_fee'        => $_POST['refund_fee'],
            'refund_post'       => $_POST['refund_post'],
            'receiver_name'     => $_POST['receiver_name'],
            'receiver_mobile'   => $_POST['receiver_mobile'],
            'receiver_address'  => $_POST['receiver_address']
        );
        
        $Model = $this->refundModel();
        $result = $Model->addByAdmin($data);
        
        if($result > 0){
            $this->success();
        }
        $this->error($Model->getError());
    }
}
?>