<?php if (!defined('THINK_PATH')) exit();?><table class="ui-table-order">
    <thead class="js-list-header-region tableFloatingHeaderOriginal" style="position: static; top: 0px; margin-top: 0px; left: 150px; z-index: 1; width: 849px;">
        <tr class="widget-list-header">
            <th class="" colspan="2" style="width: 367px">商品</th>
            <th class="price-cell" style="width: 80px">单价/数量</th>
            <th class="aftermarket-cell" style="width: 100px">售后</th>
            <th class="customer-cell" style="width: 120px">买家</th>
            <th class="time-cell" style="width: 90px">下单时间</th>
            <th class="status-cell" style="width: 110px">订单状态</th>
            <th class="pay-price-cell" style="width: 120px">总金额</th>
        </tr>
    </thead>
    <?php if(empty($list)): ?><tr class="content-row">
        <td class="text-center" colspan="8">没有相关订单</td>
    </tr>
    <?php else: ?>
    <?php if(is_array($list)): foreach($list as $tid=>$trade): ?><tbody class="widget-list-item" data-tid="<?php echo ($tid); ?>">
        <tr class="separation-row">
            <td colspan="8"></td>
        </tr>
        <tr class="header-row" title="<?php echo ($trade['seller_nick']); ?>">
            <td colspan="5">
                <?php if ($trade['edit_out_tid']) { echo '<a class="order-no js-order-no" href="javascript:;">订单号: '.$tid.'</a>'; }else{ echo '订单号:'.$tid; }?>
                <div class="order-no-1688">
                <?php if (!empty($trade['alibaba'])) { echo '<span class="label label-warning">'.($trade['alibaba'][0]['type'] == 1 ? '1688' : '淘宝').'</span>'; foreach($trade['alibaba'] as $alibaba){ if(!empty($alibaba['out_tid'])){ echo '<span title="'.($alibaba['type'] == 1 ? '1688' : '淘宝').' - '.$alibaba['buyer_login_id'].'">'.$alibaba['out_tid'].'</span>&nbsp;&nbsp;'; } } }?>
                </div>
                <?php if(!empty($trade['express'])): ?><span class="seller-send-all">运单号:
                    <?php if(is_array($trade['express'])): foreach($trade['express'] as $index=>$item): echo ($index>0?'&nbsp;&nbsp;':''); ?><a href="http://m.kuaidi100.com/result.jsp?nu=<?php echo ($item[1]); ?>" target="_blank" title="<?php echo ($item[0]); ?>"><?php echo ($item[1]); ?></a><?php endforeach; endif; ?>
                </span><?php endif; ?> 
                <!-- 
                <div class="help">
                    <span class="js-help-notes c-gray" data-class="bottom" style="cursor: help;">到店付款</span>
                </div>
                <span class="c-gray">/ 到店自提</span>
                 -->
            </td>
            <td colspan="3" class="text-right">
                <div class="order-opts-container">
                    <div class="js-memo-star-container memo-star-container">
                        <div class="opts">
                            <div class="td-cont message-opts">
                                <div class="m-opts">
                                    <?php if($trade['sync1688']): ?><a href="<?php echo __MODULE__; ?>/alibaba/syncTrade?tid=<?php echo ($tid); ?>" target="_blank">同步1688订单</a>
                                    <span>-</span><?php endif; ?>
                                    <?php if(($trade['status'] == 'toout' or $trade['status'] == 'send')): ?><a href="javascript:;" class="js-set-send">运单维护</a>
                                        <span>-</span><?php endif; ?>
                                    <?php if($trade['can_cancel'] == 1): ?><a href="javascript:;" class="js-cancel-order">取消订单</a>
                                        <span>-</span><?php endif; ?>
                                    <a class="js-set-seller-remark" rel="popover" href="javascript:;">备注</a>
                                    <span>-</span>
                                    <a href="<?php echo __MODULE__; ?>/order/detail?tid=<?php echo ($tid); ?>" class="js-order-detail" target="_blank">查看详情</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php $firstOrder=key($trade['orders']);?>
        <?php if(is_array($trade['orders'])): foreach($trade['orders'] as $index=>$order): if($index==$firstOrder){ $rowspan = count($trade['orders']);?>
        <tr class="content-row">
            <td class="image-cell">
                <img src="<?php echo ($order["pic_url"]); ?>">
            </td>
            <td class="title-cell">
                <p class="goods-title ellipsis">
                    <a href="/h5/goods?id=<?php echo ($order["goods_id"]); ?>" target="_blank" class="new-window"><?php echo ($order["title"]); ?></a>
                </p>
                <p><?php echo ($order["spec"]); ?> <a class="js-goods_feedback" data-gid="<?php echo ($order["goods_id"]); ?>" title="反馈"><i class="icon-pencil"></i></a></p>
                <p style="color: #ED5050;"><?php echo ($order['errmsg']); ?></p>
            </td>
            <td class="price-cell">
                <p><?php echo ($order["price"]); ?></p>
                <p>(<?php echo ($order["num"]); ?>件)</p>
            </td>
            <td class="aftermarket-cell" rowspan="<?php echo ($rowspan); ?>">
                <a href="javascript:;" data-tid="<?php echo ($trade['tid']); ?>" class="js-btn-cancel"><?php echo ($trade["refunded_desc"]); ?></a>
            </td>
            <td class="customer-cell" rowspan="<?php echo ($rowspan); ?>">
                <p><a href="/member?mid=<?php echo ($trade["buyer_id"]); ?>" target="_blank"><?php echo ($trade["buyer_nick"]); ?></a></p>
                <p class="user-name"><?php echo ($trade["receiver_name"]); ?></p><?php echo ($trade["receiver_mobile"]); ?>
            </td>
            <td class="time-cell" rowspan="<?php echo ($rowspan); ?>">
                <div class="td-cont"><?php echo ($trade["created"]); ?></div>
            </td>
            <td class="status-cell" rowspan="<?php echo ($rowspan); ?>" style="padding-left:0;padding-right:0">
                <div class="td-cont">
                    <p class="js-order-status"><?php echo ($trade["status_str"]); ?></p>
                </div>
            </td>
            <td class="pay-price-cell" rowspan="<?php echo ($rowspan); ?>">
                <p><?php echo ($trade['sum_fee']); ?>元</p>
                <p>(含运费<?php echo ($trade['post_fee']); ?>元)</p>
            </td>
        </tr>
        <?php }else{ ?>
        <tr class="content-row">
            <td class="image-cell">
                <img src="<?php echo ($order["pic_url"]); ?>">
            </td>
            <td class="title-cell">
                <p class="goods-title">
                    <a href="/h5/goods?id=<?php echo ($order["goods_id"]); ?>" target="_blank"get="_blank" class="new-window"><?php echo ($order["title"]); ?></a>
                </p>
                <p><?php echo ($order["spec"]); ?> <a class="js-goods_feedback" data-gid="<?php echo ($order["goods_id"]); ?>" title="反馈"><i class="icon-pencil"></i></a></p>
                <p style="color: #ED5050;"><?php echo ($order['errmsg']); ?></p>
            </td>
            <td class="price-cell">
                <p><?php echo ($order["price"]); ?></p>
                <p>(<?php echo ($order["num"]); ?>件)</p>
            </td>
        </tr>
        <?php } endforeach; endif; ?>
        <?php if(!empty($trade['buyer_remark'])): ?><tr class="remark-row buyer-msg">
            <td colspan="8">买家备注： <?php echo ($trade["buyer_remark"]); ?></td>
        </tr><?php endif; ?>
        <?php if(!empty($trade['seller_remark'])): ?><tr class="remark-row seller-msg">
            <td colspan="8">卖家备注： <?php echo ($trade["seller_remark"]); ?></td>
        </tr><?php endif; ?>
    </tbody><?php endforeach; endif; endif; ?>
</table>
<div id="pagination" style="text-align: right;" data-page="<?php echo ($page); ?>" data-total="<?php echo ($total); ?>" data-offset="<?php echo ($offset); ?>"></div>