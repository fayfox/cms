<?php 
namespace cms\helpers;

use fay\models\tables\FeedsTable;

class FeedHelper{
	/**
	 * 获取文章状态
	 * @param int $status 文章状态码
	 * @param int $delete 是否删除
	 * @param bool $coloring 是否着色（带上html标签）
	 * @return string
	 */
	public static function getStatus($status, $delete, $coloring = true){
		if($delete == 1){
			if($coloring)
				return '<span class="fc-red">回收站</span>';
			else
				return '回收站';
		}
		switch ($status) {
			case FeedsTable::STATUS_DRAFT:
				if($coloring)
					return '<span class="fc-blue">草稿</span>';
				else
					return '草稿';
				break;
			case FeedsTable::STATUS_PENDING:
				if($coloring)
					return '<span class="fc-orange">待审核</span>';
				else
					return '待审核';
				break;
			case FeedsTable::STATUS_APPROVED:
				if($coloring)
					return '<span class="fc-green">通过审核</span>';
				else
					return '通过审核';
				break;
			case FeedsTable::STATUS_UNAPPROVED:
				if($coloring)
					return '<span class="fc-purple">未通过审核</span>';
				else
					return '未通过审核';
				break;
			default:
				if($coloring)
					return '<span class="fc-yellow">未知的状态</span>';
				else
					return '未知的状态';
				break;
		}
	}
}