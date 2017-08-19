<?php
namespace phpkit\core;
use phpkit\core\Phpkit as Phpkit;

//设置modelsMetadata缓存
class BaseModel extends \Phalcon\Mvc\Model {
	protected $Pk;
	protected $TableName;
	public $findOptions = array();
	public $error;
	public function onConstruct($name = "") {
		if ($name) {
			$this->setSource($name);
		}
	}
	public function initialize() {
		$this->setConnectionService('phpkitDb');
	}

	public function getPk() {
		if ($this->Pk) {
			return $this->Pk;
		}
		$metaData = $this->getModelsMetaData();
		$PrimaryKeys = $metaData->getPrimaryKeyAttributes($this);
		$this->Pk = $PrimaryKeys[0];
		return $this->Pk;
	}

	public function getTableName() {

		return $this->TableName ? $this->TableName : $this->getSource();
	}

	//设置查询条件
	public function where($condition) {
		if (is_string($condition)) {
			$this->findOptions['conditions'] = $condition;
		} elseif (is_array($condition)) {
			$where = "1";
			$bind = array();
			foreach ($condition as $key => $value) {
				$join = is_array($value) ? $value[0] : "=";
				$andOr = is_array($value) ? $value[2] : "and";
				if (is_array($value[1])) {
					$map = "({{$key}:array})";
				} else {
					$map = ":{$key}:";
				}
				$where .= " {$andOr} {$key} {$join} $map";
				$bind[$key] = is_array($value) ? $value[1] : $value;
			}
			$this->findOptions = array('conditions' => $where, 'bind' => $bind);
		}

		return $this;
	}
	//设置查询条件
	public function bind($conditionValue) {
		$this->findOptions['bind'] = $conditionValue;
		return $this;
	}

	//加载一条数据, 默认会缓存数据
	public function load($op = array()) {

		$tableName = $this->getTableName();
		$pk = $this->getPk();
		if (is_string($op) && !is_numeric($op)) {
			$value = $op;
			$op = array();
			$op['conditions'] = "{$pk} = :{$pk}:";
			$op['bind'] = array($pk => $value);
		}

		if (is_array($op) || empty($op)) {
			$op = array_merge($this->findOptions, $op);
			ksort($op);
		}
		$res = null;
		if (is_array($op)) {
			$cacheKey = $tableName . "_" . md5(json_encode($op)); //查询缓存
			$cacheKeyPk = Phpkit::cache()->get($cacheKey); //所的缓存用主键来存
		} else {
			$cacheKeyPk = $tableName . "_" . $op; //通过主键来查询的
		}
		//通过主键缓存取值
		if ($cacheKeyPk) {
			$res = Phpkit::cache()->get($cacheKeyPk);
		}
		if (empty($res->$pk)) {
			$res = $this->findFirst($op);
			//查询到的结果
			if ($res) {
				$cacheKeyPk = $tableName . "_" . $res->$pk;
				Phpkit::cache()->save($cacheKeyPk, $res); //缓存主键结果
				if ($cacheKey) {
					Phpkit::cache()->save($cacheKey, $cacheKeyPk); //缓存查询条件
					$this->setCacheByPk($res->$pk, $cacheKey);
				}
			}
		}
		return $res;
	}

	//主键下有多少缓存
	public function setCacheByPk($id, $key) {
		$tableName = $this->getTableName();
		$cacheKeyPk = $tableName . "_keys_" . $id;
		$data = array();
		if (Phpkit::cache()->exists($cacheKeyPk)) {
			$data = (array) Phpkit::cache()->get($cacheKeyPk);
		}
		$data[$key] = 1;
		Phpkit::cache()->save($cacheKeyPk, $data);
	}

//这主键下所查询缓存
	public function getCacheByPk($id) {
		$tableName = $this->getTableName();
		$cacheKeyPk = $tableName . "_keys_" . $id;
		$data = array();
		if (Phpkit::cache()->exists($cacheKeyPk)) {
			$data = (array) Phpkit::cache()->get($cacheKeyPk);
		}
		return $data;
	}

//删除主键下所有查询缓存
	public function delCacheByPk($id) {
		$data = $this->getCacheByPk($id);
		foreach ($data as $key => $value) {
			$data = (array) Phpkit::cache()->delete($key);
		}
	}

	//删查询缓存
	public function delCache() {
		$pk = $this->getPk();
		$tableName = $this->getTableName();
		$cacheKeyPk = $tableName . "_" . $this->$pk;
		if (Phpkit::cache()->exists($cacheKeyPk)) {
			Phpkit::cache()->delete($cacheKeyPk); //缓存结果
		}
		$this->delCacheByPk($this->$pk);
		//删除get 下的缓存
		$this->DelCacheForGet();
	}

	public function afterUpdate() {

	}

	public function afterCreate() {

		//echo 'afterCreate';
	}
	//更新 添加之后清缓存
	public function afterSave() {
		$this->findOptions = array(); //清空查询
		$this->delCache();
	}
	//删除之后
	public function afterDelete() {
		///var_dump($this->Id);
		$this->findOptions = array(); //清空查询
		$this->delCache();
	}

	public function order($orderBy = "") {
		if (!empty($orderBy)) {
			$this->findOptions['order'] = $orderBy;
		}
		return $this;

	}
	public function limit($limit = array()) {
		if (!empty($limit)) {
			if (is_string($limit)) {
				$limits = explode(",", $limit);
				$arr = array('number' => intval($limits[0]) ? intval($limits[0]) : 10, 'offset' => intval($limits[1]));
			} else {
				$arr = $limit;
			}
			$this->findOptions['limit'] = $arr;
		}
		return $this;
	}

	//加载列表
	public function select($op = array()) {
		$res = array();
		if (is_array($op)) {
			$op = array_merge($this->findOptions, $op);
			ksort($op);
		}
		if (empty($op['limit'])) {
			//没有使用limit 全查，需要缓存结果
			$tableName = $this->getTableName();
			$cacheKey = $tableName . "_get_" . md5(json_encode($op));
			if (Phpkit::cache()->exists($cacheKey)) {
				$res = Phpkit::cache()->get($cacheKey);
			} else {
				$res = $this->find($op);
				Phpkit::cache()->save($cacheKey, $res);
				$this->AddCacheForGet($cacheKey);
			}
		} else {
			$res['recordsFiltered'] = $this->count(array('conditions' => $op['conditions']));
			$res['recordsTotal'] = $this->count();
			$res['list'] = $this->find($op);
		}
		$this->findOptions = array(); //清空查询
		return $res;
	}
//添加get缓存
	public function AddCacheForGet($key) {
		$tableName = $this->getTableName();
		$cacheKey = $tableName . "_get";
		$data = array();
		if (Phpkit::cache()->exists($cacheKey)) {
			$data = (array) Phpkit::cache()->get($cacheKey);
		}
		$data[$key] = 1;
		Phpkit::cache()->save($cacheKey, $data);

	}
//删除一个表get缓存
	public function DelCacheForGet() {
		$data = $this->GetCacheForGet();
		foreach ($data as $key => $value) {
			Phpkit::cache()->delete($key);
		}

	}
//一个表下有多少get缓存
	public function GetCacheForGet() {
		$tableName = $this->getTableName();
		$cacheKey = $tableName . "_get";
		$data = array();
		if (Phpkit::cache()->exists($cacheKey)) {
			$data = (array) Phpkit::cache()->get($cacheKey);
		}
		return $data;
	}

	//删除
	public function remove($op = array()) {
		if (!is_array($op) && !empty($op)) {
			$res = $this->load($op);
			$lists = $res ? array($res) : array();

		}
		if (is_array($op)) {
			$res = $this->get($op);
			$lists = $res['list'] ? $res['list'] : array();
		}
		$this->deleteLists = $lists;
		$flag = 1;
		if (!empty($lists)) {
			foreach ($lists as $key => $list) {
				if ($list->delete() == false) {
					foreach ($list->getMessages() as $message) {
						$this->error[] = $message;
					}
					$flag = $flag * 0;
				} else {
					$flag = $flag * 1;
				}
			}
		} else {
			$this->error[] = "没有查询到删除数据";
		}
		$this->findOptions = array(); //清空查询
		return $flag;
	}

}