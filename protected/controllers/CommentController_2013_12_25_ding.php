<?php/** * 评论功能 * @author Frank */class CommentController extends Controller {	/**	 * Mongodb 连接句柄	 *	 * @var $conn	 */	public $conn;	/**	 * 表空间	 *	 * @var $collection	 */	public $collection;	/**	 * 执行query	 *	 * @var $query	 */	private $query;	/**	 * 评论关系总表 结果集	 *	 * @var $relation	 */	private $relation;	/**	 * 加工后关系列表 主要用于盖楼	 *	 * @var $relation_list	 */	private $relation_list = array ();	/**	 * 分割符	 *	 * @var $identification	 */	private $identification = '_';	/**	 * 用于取所有不重复的数据	 *	 * @var $product	 */	private $product = array ();	/**	 * 临时转换数据	 *	 * @var $_temp	 */	private $_temp;	/**	 * 标记文件用于分割 '_'	 *	 * @var $_flag	 */	private $_flag;	/**	 * 拆箱	 *	 * @var $devanning	 */	private $devanning;	/**	 * 装箱	 *	 * @var $packing	 */	private $packing;	/**	 * 评论最终数据	 *	 * @var $floorData	 */	private $floorData;	/**	 * 评论单项数据	 *	 * @var array	 */	private $commentItemData;	/**	 * 引用回复时commentid 取单项数据关系结构	 *	 * @var string	 */	private $commentItemQuote;	/**	 * 显示总评论数	 *	 * @var $relation_count	 */	private $relation_count;	/**	 * @初始化应用数据	 */	public function init() {		$this->layout = false;		parent::init ();		$this->mongoInit ();	}	public function actionIndex() {		/**		 * 测试数据		 * db.comment.remove({"_id":"0883B740-C3AE-8EF0-C03E-15128FEF6142"})		 * db.comment.insert({ "_id" : "0883B740-C3AE-8EF0-C03E-15128FEF6142", "0" : "1_2_3_4_5", "1" : "1_3_4_5", "2" : "6_2_1_8", "3" : "1_9_2_3", "4" : "4_5_6_7", "5" : "1_2_8_9","5":"5","6":"5_2_9","7":"8","9":"1_9_7_5_3_7_499_23432_23423","10":"312321_23432_444_222","11":"1234_32423_22_12313_23123_3123_23432_455_21312_5436_645_4353_1234_5463546_7657_232_324_21312_4234_56435_2234_" })		 */		$this->getRelation ( '0883B740-C3AE-8EF0-C03E-15128FEF6142' );		if (empty ( $this->relation_list )) {			$this->floorData = false;			$this->render ( 'index', array (					'html' => $this->floorData 			) );			exit ( 0 );		} else {			$this->packingData (); // 根据关系取出所有评论项			$this->getCommentItem (); // 根据id取出全部评论数据			$this->makeFloor (); // 根据关系建造楼前台渲染			$this->render ( 'index', array (					'html' => $this->floorData 			) );			unset ( $this->floorData );			exit ( 0 );		}	}	/**	 * 数据库初始化，正式项目在配置文件中生成	 * @mongodb	 */	public function mongoInit() {		try {			$this->conn = new MongoClient ();			$this->collection = $this->conn->selectCollection ( 'm', 'comment' );		} catch ( MongoConnectionException $e ) {			echo '<p>Couldn\'t connect to mongodb, is the "mongo" process running?</p>';			exit ();		}	}	/**	 * 指定新闻ID的所有评论关系数据	 *	 * @param int $id        		 */	public function getRelation($id) {		$this->query = array (				'_id' => $id 		);		$this->relation = $this->collection->findOne ( $this->query );		if ($this->relation) {			$this->relation_count = $this->relation ['count'];			$this->relation_list = $this->relation ['index'];		} else {			$this->relation_list = false;		}	}	/**	 * *	 * @取出要查找的评论id去掉重复值	 * @强制转化为数字键值拆分数组，	 */	public function packingData() {		foreach ( $this->relation_list as $v ) {			$this->_flag = ($v != end ( $this->relation_list )) ? $this->identification : '';			$this->_temp .= $v . $this->_flag;		}		$this->devanning = implode ( ',', array_unique ( explode ( '_', $this->_temp ) ) );		$this->product = explode ( ',', $this->devanning );		// foreach ( $this->packing as $value ) {		// $this->product [] = ( string ) $value;//只有id为纯数字时候，才强制转换		// }	}	/**	 * *	 * 建楼主入口	 * 根据节点关系查找楼层数据	 *	 * @throws Exception	 */	public function makeFloor() {		try {			if (is_array ( $this->relation_list )) {				krsort ( $this->relation_list );				foreach ( $this->relation_list as $key => $value ) {					$this->floorData [] = $this->getItem ( $key, $value );				}			} else {				throw new Exception ( '盖楼关系数据有问题完法进行，请查看程序' );			}		} catch ( Exception $e ) {			echo $e->getMessage () . __LINE__;		}	}	/**	 * 根据评论ID展示楼层	 * 父亲$parentkey 父亲临时关系$item	 *	 * @param int $parentkey        		 * @param string $item        		 * @throws Exception	 * @return string	 */	public function getItem($parentkey, $child) {		try {			$_frontHtml = '';			$items = explode ( '_', $child );			$lev = count ( $items ); // 总楼层级			if (is_array ( $items )) {				if ($lev == 1) {					$_foors = '';				} else {					$_foors = 1;				}				foreach ( $items as $key => $v ) {					if (is_array ( $this->commentItemData )) {						$count = count ( $this->commentItemData );						for($i = 0; $i < $count; $i ++) {							if ($this->commentItemData [$i] ['id'] == $v) {								$author = $this->commentItemData [$i] ['author'];								$comment = $this->substrCut ( $this->commentItemData [$i] ['comment'], 150 ); // 取50个汉字								$date = $this->commentItemData [$i] ['date'];								$commentid = $this->commentItemData [$i] ['id'];								$_id = "'" . $this->commentItemData [$i] ['_id'] . "'";								$support = isset ( $this->commentItemData [$i] ['support'] ) ? $this->commentItemData [$i] ['support'] : '0'; // 顶数据							}						}						$frontDiv = $v . $parentkey . $key; // 构造回复评论级						$_floor = $_foors ? $_foors . '楼' : ''; // 如果是1层楼则不显示楼层						$_form = '<div class="box" style="display:none" id="shwo_' . $frontDiv . '">								  <form action="###" id="' . $frontDiv . '" method="post" enctype="multipart/form-data">								    <div> 								      <br />								      内容:								      <textarea name="content" id="content_' . $frontDiv . '" cols="60" rows="5" style="padding:10px;">' . $comment . '</textarea>								      <br>								      <input type="hidden" name="authorid" id="authorid_' . $frontDiv . '" value="' . $author . '">								      <input type="hidden" name="articleid" id="articleid_' . $frontDiv . '" value="0883B740-C3AE-8EF0-C03E-15128FEF6142">								      <input type="hidden" name="commentid" id="commentid_' . $frontDiv . '" value=' . $_id . '>								      <input type="hidden" name="id" id="id_' . $frontDiv . '" value="' . $v . '">								      <br>								      <br>								      <input type="button" name="button" value="提交" onclick="post(' . $frontDiv . ',' . $v . ');" class="btn">								    </div>								  </form>								</div>';						$_frontHtml = '<div class="box" id="dwnews_' . $v . '">' . $_frontHtml . '作者:' . $author . '<h1>' . $date . '</h1>' . $comment . '<span>' . $_floor . '</span><ul><li><a href="javascript:show(' . $frontDiv . ',' . $commentid . ');">回复</a></li><li><a href="javascript:Support(' . $_id . ',' . $frontDiv . ')">顶</a><span id=support_show_' . $frontDiv . '>[' . $support . ']</span></li></ul>' . $_form . '</div>';						$_foors ++;						unset ( $frontDiv );					} else {						throw new Exception ( '实际评论单项数据' );					}				}				return $_frontHtml;			} else {				throw new Exception ( '根据评论ID展示楼层有问题' );			}		} catch ( Exception $e ) {			echo $e->getMessage () . __LINE__;		}	}	/**	 * 评论的所有数据集	 *	 * @throws Excepition	 */	public function getCommentItem() {		try {			if (is_array ( $this->product )) {				$query = array (						'id' => array (								'$in' => $this->product 						) 				);				$cursor = $this->collection->find ( $query );				foreach ( $cursor as $v ) {					$this->commentItemData [] = $v;				}			} else {				throw new Excepition ( '单项评论in操作有问题' );			}		} catch ( Exception $e ) {			echo $e->getMessage () . __LINE__;		}	}	/**	 * 字符串截取中文或英文	 *	 * @param string $str_cut        		 * @param string $length        		 * @return string	 */	public function substrCut($str_cut, $length) {		if (strlen ( $str_cut ) > $length) {			for($i = 0; $i < $length; $i ++)				if (ord ( $str_cut [$i] ) > 128)					$i ++;			$str_cut = substr ( $str_cut, 0, $i ) . "...";		}		return $str_cut;	}	/**	 * *	 * @主要用于生成hash评论主键	 *	 * @param unknown $pares        		 */	public function makeHash($pares) {		return hash ( 'sha1', $pares . time () );	}	/**	 * 生成不重复的随机数	 *	 * @param unknown $param        		 * @return string	 */	public function makeRand($param) {		return $param . time () . rand ( 0, 5 );	}	/**	 * @前台评论	 */	public function actionPost() {		$auto_increment_id = '' . $this->autoIncrementId ( 'dwnews' ) . '';		$relation = Yii::app ()->request->getParam ( 'articleid' );		$this->getRelation ( $relation );		if ($this->relation_count == false) {			$init_relation = array (					"_id" => $relation,					"index" => array (),					"count" => 0 			);			$this->collection->insert ( $init_relation );		}		$this->getRelation ( $relation );		// self::debug ( ( int ) $this->relation_count );		// 评论关系表数据变化		$doItem = Yii::app ()->request->getParam ( 'doitem' );		if ($doItem == 'post') {			$postData = array (					'id' => $auto_increment_id,					'author' => Yii::app ()->request->getParam ( 'author' ),					'comment' => Yii::app ()->request->getParam ( 'content' ),					'support' => 0,					'date' => new MongoTimestamp (),					'type' => 'dwnews',					'quote' => '0' 			);			if ($this->collection->insert ( $postData )) {				$data = array (						'post' => 'success',						'stat' => 0 				);			} else {				$data = array (						'post' => 'error',						'stat' => 1 				);			}			$this->updateRelation ( $relation, $auto_increment_id );			$this->makeJson ( $data );		} elseif ($doItem == 'ajaxdo') {			// self::debug ( Yii::app ()->request->getParam () );			$commentid = Yii::app ()->request->getParam ( 'commentid' );			$topid = Yii::app ()->request->getParam ( 'topid' );			// echo $commentid;exit;			if ($commentid) {				$this->getCommentItemQuote ( $commentid );			} else {				$this->makeJson ( array (						'stat' => 1,						'val' => 'url非法' 				) );			}			if ($this->commentItemQuote ['quote'] == '0') {				$quote = $topid . '_' . $auto_increment_id;			} else {				$quote = $this->commentItemQuote ['quote'] . '_' . $auto_increment_id;			}			;			$postData = array (					'id' => $auto_increment_id,					'author' => Yii::app ()->request->getParam ( 'author' ),					'comment' => Yii::app ()->request->getParam ( 'content' ),					'support' => $this->commentItemQuote ['support'],					'date' => new MongoTimestamp (),					'type' => 'dwnews',					'quote' => $quote 			);			if ($this->collection->insert ( $postData )) {				$data = array (						'post' => 'success',						'stat' => 0 				);			} else {				$data = array (						'post' => 'error',						'stat' => 1 				);			}			$this->updateRelation ( $relation, $auto_increment_id, $quote );			$this->makeJson ( $data );		}	}	/**	 * 添加新评论 更新评论统计数	 *	 * @param unknown $relation        		 * @param unknown $temp_id        		 */	private function updateRelation($relation, $auto_increment_id, $type = null) {		$this->collection->update ( array (				'_id' => $relation 		), array (				'$push' => array (						'index' => ($type != null) ? $type : $auto_increment_id 				) 		) );		$this->collection->update ( array (				'_id' => $relation 		), array (				'$inc' => array (						'count' => 1 				) 		) );		// 最后回复要对自己关系更新		if ($type != null) {						$this->collection->update ( array (					'_id' => $relation 			), array (					'$set' => array (							'quote' => ( int ) $type 					) 			) );		}	}	/**	 * 检测commentid是否合法，如果不合法退出执行	 *	 * @param unknown $commentid        		 * @return boolean	 */	private function checkCommentid($commentid) {		$commentid = true;		return $commentid ? $commentid : 0;	}	/**	 * 取引用评论id关系，如果为空则是单条评论	 *	 *	 * @param unknown $commentid        		 */	public function getCommentItemQuote($commentid) {		$Quote = $this->collection->findOne ( array (				'_id' => new MongoId ( $commentid ) 		) );		// self::debug ( $Quote );		try {			if ($Quote) {				$this->commentItemQuote = $Quote;			} else {				throw new Exception ( '单项数据关系有问题' );			}		} catch ( Exception $e ) {			echo $e->getMessage () . __LINE__;		}	}	/**	 * @顶操作	 */	public function actionSupport() {		$commentid = Yii::app ()->request->getParam ( 'commentid' );				// 更新数据		$this->collection->update ( array (				'_id' => new MongoId ( $commentid ) 		), array (				'$inc' => array (						'support' => 1 				) 		) );		$cursor = $this->collection->find ( array (				'_id' => new MongoId ( $commentid ) 		) );				foreach ( $cursor as $value ) {			$support = isset ( $value ['support'] ) ? $value ['support'] : 0;		}		Yii::app ()->session ['support_' . $commentid] = $commentid;		$data = array (				'support' => $support,				'stat' => 'ok' 		);		$this->makeJson ( $data );	}	/**	 * 前台ajax提交数据返回操作	 *	 * @param unknown $data        		 */	public function makeJson($data) {		$callback = Yii::app ()->request->getParam ( 'callback' );		echo $callback . "(" . CJSON::encode ( $data ) . ")";		exit ( 0 );	}	/**	 * 生成自增id	 *	 * @param unknown $namespace        		 * @param array $option        		 * @return Ambigous <number>	 */	public function autoIncrementId($namespace, array $option = array()) {		$option += array (				'init' => 1,				'step' => 1 		);		$instance = $this->conn->selectCollection ( 'm', 'seq' );		$seq = $instance->db->command ( array (				'findAndModify' => 'seq',				'query' => array (						'_id' => $namespace 				),				'update' => array (						'$inc' => array (								'id' => $option ['step'] 						) 				),				'new' => true 		) );		if (isset ( $seq ['value'] ['id'] )) {			return $seq ['value'] ['id'];		}		$instance->insert ( array (				'_id' => $namespace,				'id' => $option ['init'] 		) );		return $option ['init'];	}	/**	 * 调试程序打印数据	 *	 * @param array $str        		 */	public static function debug($str) {		echo '<pre>';		print_r ( $str );		exit ( 0 );	}}?>