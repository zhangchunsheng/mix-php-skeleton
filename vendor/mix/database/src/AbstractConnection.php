<?php

namespace Mix\Database;

use Mix\Database\Event\ExecutedEvent;
use Mix\Database\Helper\BuildHelper;
use Mix\Database\Query\Expression;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class AbstractConnection
 * @package Mix\Database
 * @author liu,jian <coder.keda@gmail.com>
 */
abstract class AbstractConnection
{

    /**
     * 驱动
     * @var Driver
     */
    public $driver;

    /**
     * 事件调度器
     * @var EventDispatcherInterface
     */
    public $dispatcher;

    /**
     * PDOStatement
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * sql
     * @var string
     */
    protected $sql = '';

    /**
     * params
     * @var array
     */
    protected $params = [];

    /**
     * values
     * @var array
     */
    protected $values = [];

    /**
     * 查询数据
     * @var array
     */
    protected $queryData = [];

    /**
     * AbstractConnection constructor.
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * 连接
     * @throws \PDOException
     */
    public function connect()
    {
        $this->driver->connect();
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        $this->statement = null;
        $this->driver->close();
    }

    /**
     * 构建查询片段
     * @param array $item
     * @return string|bool
     */
    protected function buildQueryFragment(array $item)
    {
        if (isset($item['if']) && $item['if'] == false) {
            return false;
        }
        if (isset($item['params'])) {
            $this->bindParams($item['params']);
        }
        return array_shift($item);
    }

    /**
     * 准备执行语句
     * @param string|array $sql
     * @return $this
     */
    public function prepare($sql)
    {
        // 清扫数据
        $this->sql    = '';
        $this->params = [];
        $this->values = [];
        // 字符串构建
        if (is_string($sql)) {
            $this->sql = $sql;
        }
        // 数组构建
        if (is_array($sql)) {
            $fragments = [];
            foreach ($sql as $item) {
                $fragment = $this->buildQueryFragment($item);
                if ($fragment) {
                    $fragments[] = $fragment;
                }
            }
            $this->sql = implode(' ', $fragments);
        }
        // 保存SQL
        $this->queryData = [$this->sql, [], [], 0];
        // 返回
        return $this;
    }

    /**
     * 绑定参数
     * @param array $data
     * @return $this
     */
    public function bindParams(array $data)
    {
        $this->params = array_merge($this->params, $data);
        return $this;
    }

    /**
     * 绑定值
     * @param array $data
     * @return $this
     */
    public function bindValues(array $data)
    {
        $this->values = array_merge($this->values, $data);
        return $this;
    }

    /**
     * 返回当前PDO连接是否在事务内（在事务内的连接回池会造成下次开启事务产生错误）
     * @return bool
     */
    public function inTransaction(): bool
    {
        $pdo = $this->driver->instance();
        return (bool)($pdo ? $pdo->inTransaction() : false);
    }

    /**
     * 绑定数组参数
     * @param $sql
     * @param $params
     * @return array
     */
    protected function bindArrayParams($sql, $params)
    {
        foreach ($params as $key => $item) {
            if (is_array($item)) {
                unset($params[$key]);
                $key = substr($key, 0, 1) == ':' ? $key : ":{$key}";
                $sql = str_replace($key, implode(', ', $this->quotes($item)), $sql);
            }
        }
        return [$sql, $params];
    }

    /**
     * 构建查询
     */
    protected function build()
    {
        if (!empty($this->params)) {
            // 准备与参数绑定
            // 原始方法
            foreach ($this->params as $key => $item) {
                if ($item instanceof Expression) {
                    unset($this->params[$key]);
                    $key       = substr($key, 0, 1) == ':' ? $key : ":{$key}";
                    $this->sql = str_replace($key, $item->getValue(), $this->sql);
                }
            }
            // 有参数
            list($sql, $params) = $this->bindArrayParams($this->sql, $this->params);
            $this->statement = $this->driver->instance()->prepare($sql);
            $this->queryData = [$sql, $params, [], 0]; // 必须在 bindParam 前，才能避免类型被转换
            foreach ($params as $key => &$value) {
                $this->statement->bindParam($key, $value);
            }
        } elseif (!empty($this->values)) {
            // 批量插入
            $this->statement = $this->driver->instance()->prepare($this->sql);
            $this->queryData = [$this->sql, [], $this->values, 0];
            foreach ($this->values as $key => $value) {
                $this->statement->bindValue($key + 1, $value);
            }
        } else {
            // 无参数
            $this->statement = $this->driver->instance()->prepare($this->sql);
            $this->queryData = [$this->sql, [], [], 0];
        }
    }

    /**
     * 清扫构建查询数据
     */
    protected function clear()
    {
        $this->sql    = '';
        $this->params = [];
        $this->values = [];
    }

    /**
     * 获取当前时间, 单位: 秒, 粒度: 微秒
     * @return float
     */
    protected static function microtime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    
    /**
     * 调度事件
     * @param string $sql
     * @param array $bindings
     * @param float $time
     * @param string|null $error
     */
    protected function dispatch(string $sql, array $bindings, float $time, string $error = null)
    {
        if (!$this->dispatcher) {
            return;
        }
        $event           = new ExecutedEvent();
        $event->sql      = $sql;
        $event->bindings = $bindings;
        $event->time     = $time;
        $event->error    = $error;
        $this->dispatcher->dispatch($event);
    }

    /**
     * 执行SQL语句
     * @return bool
     */
    public function execute(): bool
    {
        $microtime = static::microtime();
        try {
            $this->build();
            $success = $this->statement->execute();
        } catch (\Throwable $ex) {
            $message = sprintf('%s %s in %s on line %s', $ex->getMessage(), get_class($ex), $ex->getFile(), $ex->getLine());
            $code    = $ex->getCode();
            $error   = sprintf('[%d] %s', $code, $message);
            throw $ex;
        } finally {
            $time               = round((static::microtime() - $microtime) * 1000, 2);
            $this->queryData[3] = $time;
            $this->clear();

            $log = $this->getLastLog();
            $this->dispatch($log['sql'], $log['bindings'], $log['time'], $error ?? null);
        }
        // 返回
        return $success;
    }

    /**
     * 返回结果集
     * @return \PDOStatement
     */
    public function query(): \PDOStatement
    {
        $this->execute();
        return $this->statement;
    }

    /**
     * 返回一行
     * @param int $fetchStyle
     * @return array|object
     */
    public function queryOne(int $fetchStyle = null)
    {
        $this->execute();
        $fetchStyle = $fetchStyle ?: $this->driver->options()[\PDO::ATTR_DEFAULT_FETCH_MODE];
        return $this->statement->fetch($fetchStyle);
    }

    /**
     * 返回多行
     * @param int $fetchStyle
     * @return array
     */
    public function queryAll(int $fetchStyle = null): array
    {
        $this->execute();
        $fetchStyle = $fetchStyle ?: $this->driver->options()[\PDO::ATTR_DEFAULT_FETCH_MODE];
        return $this->statement->fetchAll($fetchStyle);
    }

    /**
     * 返回一列 (默认第一列)
     * @param int $columnNumber
     * @return array
     */
    public function queryColumn(int $columnNumber = 0): array
    {
        $this->execute();
        $column = [];
        while ($row = $this->statement->fetchColumn($columnNumber)) {
            $column[] = $row;
        }
        return $column;
    }

    /**
     * 返回一个标量值
     * @return mixed
     */
    public function queryScalar()
    {
        $this->execute();
        return $this->statement->fetchColumn();
    }

    /**
     * 返回最后插入行的ID或序列值
     * @return string
     */
    public function getLastInsertId(): string
    {
        return $this->driver->instance()->lastInsertId();
    }

    /**
     * 返回受上一个 SQL 语句影响的行数
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * 返回最后的SQL语句
     * @return string
     */
    public function getLastSql(): string
    {
        $sql    = '';
        $params = $values = [];
        !empty($this->queryData) and list($sql, $params, $values) = $this->queryData;
        if (empty($params) && empty($values)) {
            return $sql;
        }
        $values = $this->quotes($values);
        $params = $this->quotes($params);
        // 先处理 values，避免 params 中包含 ? 号污染结果
        $sql = vsprintf(str_replace('?', '%s', $sql), $values);
        // 处理 params
        foreach ($params as $key => $value) {
            $key = substr($key, 0, 1) == ':' ? $key : ":{$key}";
            $sql = str_replace($key, $value, $sql);
        }
        return $sql;
    }

    /**
     * 获取最后的日志
     * @return array
     */
    public function getLastLog(): array
    {
        $sql    = '';
        $params = $values = [];
        $time   = 0;
        !empty($this->queryData) and list($sql, $params, $values, $time) = $this->queryData;
        return [
            'sql'      => $sql,
            'bindings' => $values ?: $params,
            'time'     => $time,
        ];
    }

    /**
     * 给字符串加单引号
     * @param $var
     * @return array|string
     */
    protected function quotes($var)
    {
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = $this->quotes($v);
            }
            return $var;
        }
        return is_string($var) ? $this->driver->instance()->quote($var) : $var;
    }

    /**
     * 插入
     * @param string $table
     * @param array $data
     * @return $this
     */
    public function insert(string $table, array $data)
    {
        $keys   = array_keys($data);
        $fields = array_map(function ($key) {
            return ":{$key}";
        }, $keys);
        $sql    = "INSERT INTO `{$table}` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $fields) . ")";
        $this->prepare($sql);
        $this->bindParams($data);
        return $this;
    }

    /**
     * 批量插入
     * @param string $table
     * @param array $data
     * @return $this
     */
    public function batchInsert(string $table, array $data)
    {
        $keys   = array_keys($data[0]);
        $sql    = "INSERT INTO `{$table}` (`" . implode('`, `', $keys) . "`) VALUES ";
        $values = [];
        $subSql = [];
        foreach ($data as $item) {
            $placeholder = [];
            foreach ($keys as $key) {
                $value = $item[$key];
                // 原始方法
                if ($value instanceof Expression) {
                    $placeholder[] = $value->getValue();
                    continue;
                }
                $values[]      = $value;
                $placeholder[] = '?';
            }
            $subSql[] = "(" . implode(', ', $placeholder) . ")";
        }
        $sql .= implode(', ', $subSql);
        $this->prepare($sql);
        $this->bindValues($values);
        return $this;
    }

    /**
     * 更新
     * @param string $table
     * @param array $data
     * @param array $where
     * @return $this
     */
    public function update(string $table, array $data, array $where)
    {
        if (!BuildHelper::isMulti($where)) {
            $where = [$where];
        }
        list($dataSql, $dataParams) = BuildHelper::data($data);
        list($whereSql, $whereParams) = BuildHelper::where($where);
        $this->prepare([
            ["UPDATE `{$table}`"],
            ["SET {$dataSql}", 'params' => $dataParams],
            ["WHERE {$whereSql}", 'params' => $whereParams],
        ]);
        return $this;
    }

    /**
     * 删除
     * @param string $table
     * @param array $where
     * @return $this
     */
    public function delete(string $table, array $where)
    {
        if (!BuildHelper::isMulti($where)) {
            $where = [$where];
        }
        list($sql, $params) = BuildHelper::where($where);
        $this->prepare([
            ["DELETE FROM `{$table}`"],
            ["WHERE {$sql}", 'params' => $params],
        ]);
        return $this;
    }

    /**
     * 自动事务
     * @param \Closure $closure
     * @throws \Throwable
     */
    public function transaction(\Closure $closure)
    {
        $this->beginTransaction();
        try {
            call_user_func($closure, $this);
            $this->commit();
        } catch (\Throwable $ex) {
            $this->rollBack();
            throw $ex;
        }
    }

    /**
     * 开始事务
     * @return $this
     * @throws \PDOException
     */
    public function beginTransaction()
    {
        if (!$this->driver->instance()->beginTransaction()) {
            throw new \PDOException('Begin transaction failed');
        }
        return $this;
    }

    /**
     * 提交事务
     * @throws \PDOException
     */
    public function commit()
    {
        if (!$this->driver->instance()->commit()) {
            throw new \PDOException('Commit transaction failed');
        }
    }

    /**
     * 回滚事务
     * @throws \PDOException
     */
    public function rollback()
    {
        if (!$this->driver->instance()->rollBack()) {
            throw new \PDOException('Rollback transaction failed');
        }
    }

    /**
     * 启动查询生成器
     * @param string $table
     * @return QueryBuilder
     */
    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder($this))->table($table);
    }

}
