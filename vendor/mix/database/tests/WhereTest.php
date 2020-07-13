<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WhereTest extends TestCase
{

    // ['id', 'in', [2, 3]]
    public function testIn(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn   = conn();
            $result = $conn->table('users')
                ->where(['id', 'in', [2, 3]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id IN (2, 3)", $sql);
        };
        run($func);
    }

    // [['id', '=', 1], ['or', ['id', 'in', [2, 3]]]]
    public function testOrIn(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn   = conn();
            $result = $conn->table('users')
                ->where([['id', '=', 1], ['or', ['id', 'in', [2, 3]]]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 OR id IN (2, 3)", $sql);
        };
        run($func);
    }

    public function testAnd(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn   = conn();
            $result = $conn->table('users')
                ->where(['id', '=', 1])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1", $sql);

            $result = $conn->table('users')
                ->where(['id', '=', 1])
                ->where(['id', '=', 2])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 AND id = 2", $sql);

            $result = $conn->table('users')
                ->where([['id', '=', 1], ['id', '=', 2]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 AND id = 2", $sql);
        };
        run($func);
    }

    // ['or', ['foo', '=', 1]]
    public function testOr(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn   = conn();
            $result = $conn->table('users')
                ->where(['id', '=', 1])
                ->where(['or', ['id', '=', 2]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 OR id = 2", $sql);

            $result = $conn->table('users')
                ->where([['id', '=', 1], ['or', ['id', '=', 2]]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 OR id = 2", $sql);

            $result = $conn->table('users')
                ->where(['id', '=', 1])
                ->where(['or', [['id', '=', 2], ['id', '=', 3]]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 OR (id = 2 AND id = 3)", $sql);

            $result = $conn->table('users')
                ->where([['id', '=', 1], ['or', [['id', '=', 2], ['id', '=', 3]]]])
                ->get();
            $sql    = $conn->getLastSql();
            $_this->assertContains("SELECT * FROM users WHERE id = 1 OR (id = 2 AND id = 3)", $sql);
        };
        run($func);
    }

    // 当参数为空但未使用 is null 时抛出异常
    // ['foo', '=', null]
    public function testNull(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn = conn();
            try {
                $result = $conn->table('users')->where([
                    ['id', '=', null],
                ])->get();
            } catch (\Throwable $ex) {
                $_this->assertContains('Invalid where format', $ex->getMessage());
            }
        };
        run($func);
    }

    // 第一个字段是参数时抛出异常
    // [1, '=', 'id']
    public function testReverse(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn = conn();
            try {
                $result = $conn->table('users')->where([
                    [1, '=', 'id'],
                ])->get();
            } catch (\Throwable $ex) {
                $_this->assertContains('Invalid where format', $ex->getMessage());
            }
        };
        run($func);
    }

    // raw test
    public function testExpression()
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $conn   = conn();
            
            $result = $conn->table('users')->where([
                ['id', '>', \Mix\Database\Database::raw('MOD(5,2)')],
            ])->first();
            $_this->assertTrue((bool)$result);

            $result = $conn->table('users')->where([
                [\Mix\Database\Database::raw('CHAR_LENGTH(id)'), '=', 1],
            ])->get();
            $_this->assertTrue((bool)$result);
        };
        run($func);
    }

}
