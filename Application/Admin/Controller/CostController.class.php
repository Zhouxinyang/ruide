<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 订单成本核算
 * @author lanxuebao
 */
class CostController extends CommonController
{
    public function index(){
        $aliList = array();
        
        $filename = 'hesuan.xlsx';
        $loginId = '亲客微商';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
        // 支付宝记录
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $tid = trim($worksheet->getCell('A'.$i)->getValue());
            if(!is_numeric($tid)){
                continue;
            }
            
            $type = trim($worksheet->getCell('G'.$i)->getValue());
            $type = $type == '淘宝' ? 2 : 1;
            $title = trim($worksheet->getCell('F'.$i)->getValue());
            
            $aliList[$tid] = array(
                'payment' => trim($worksheet->getCell('B'.$i)->getValue()),
                'created' => trim(gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($worksheet->getCell('C'.$i)->getValue()))),
                'pay_time' => trim(gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($worksheet->getCell('D'.$i)->getValue()))),
                'seller_nick' => trim($worksheet->getCell('E'.$i)->getValue()),
                'products' => addslashes($title),
                'type'    => $type,
                'line'    => $i
            );
        }
        
        // 组合数据
        $sql = "";
        foreach($aliList as $outerId=>$ali){
            $sql .= "UPDATE alibaba_trade SET payment={$ali['payment']}, pay_time='{$ali['pay_time']}' WHERE out_tid={$outerId};<br>";
        }
        print_data($sql);
        
        $today = date('Y-m-d H:i:s');
        
        // 组合数据
        $sql = "INSERT INTO alibaba2_trade2(out_tid, payment, created, order_time, do_cost, error_msg, products, buyer_login_id, `status`, seller_nick, type) VALUES";
        foreach($aliList as $outerId=>$ali){
            $sql .= "<br>({$outerId}, {$ali['payment']}, '{$today}', '{$ali['created']}', 1,'手动补入数据', '{$ali['products']}', '{$loginId}', 'end', '{$ali['seller_nick']}', {$ali['type']}),";
        }
        
        print_data($sql);
    }
    
    
    public function price(){
        $result = $list = array();
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $goodsId = trim($worksheet->getCell('Q'.$i)->getValue());
            if(!is_numeric($goodsId)){
                continue;
            }
        
            $price = trim($worksheet->getCell('B'.$i)->getValue());
            if(!isset($list[$goodsId])){
                $list[$goodsId] = array($price);
            }else if(!in_array($price, $list[$goodsId])){
                $list[$goodsId][] = $price;
                if(!in_array($goodsId, $result)){
                    $result[] = $goodsId;
                }
            }
        }
        
        print_data($result);
    }
    
    public function spec(){
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $cell = $worksheet->getCell('E'.$i);
            $json = trim($cell->getValue());
            $spec = get_spec_name($json);
            $cell->setValue($spec);
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
    
    public function num(){
        $result = $result1 = array();
        
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $tid = trim($worksheet->getCell('A'.$i)->getValue());
            $num = trim($worksheet->getCell('I'.$i)->getValue());
            
            if(!isset($result1[$tid])){
                $result1[$tid] = $num;
            }else{
                $result1[$tid] += $num;
            }
        }
        
        $sended = $this->sendexcel();
        
        foreach ($result1 as $tid=>$num){
            if(!isset($sended[$tid])){
                $result[$tid] = '未发货';
            }else if($num != $sended[$tid]){
                $result[$tid] = $sended[$tid];
            }
        }
        
        print_data($result);
    }
    
    public function sendexcel(){
        $result = array();
        Vendor('PHPExcel.PHPExcel.IOFactory');
        
        $dir = realpath("../send");  //要获取的目录
        $dh = opendir($dir);
        while ($file = readdir($dh)){
            if($file!="." && $file!=".."){
                $filename = $dir.'\\'.$file;
                $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
                $worksheet = $objPHPExcel->getSheet(1);
                $rows = $worksheet->getHighestRow();
                for($i=2; $i<=$rows; $i++){
                    $tid = trim($worksheet->getCell('A'.$i)->getValue());
                    $num = trim($worksheet->getCell('D'.$i)->getValue());
        
                    if(!isset($result[$tid])){
                        $result[$tid] = $num;
                    }else{
                        $result[$tid] += $num;
                    }
                }
            }
        }
        closedir($dh);
        return $result;
    }
    
    public function asdfasd(){
        $Model = M();
        $_oldList = $Model->query("SELECT * FROM ywsend");
        $goodsList = array();
        foreach ($_oldList as $item){
            $goodsList[$item['goods_id'].$item['spec']] = $item['cost'];
        }
        
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $filename = '../hesuan.xlsx';
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $goodsId = trim($worksheet->getCell('F'.$i)->getValue());
            $spec = trim($worksheet->getCell('G'.$i)->getValue());
            $cost = trim($worksheet->getCell('B'.$i)->getValue());
            if(is_numeric($cost)){
                continue;
            }else if(isset($goodsList[$goodsId.$spec])){
                $worksheet->getCell('B'.$i)->setValue($goodsList[$goodsId.$spec]);
            }
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
}
?>