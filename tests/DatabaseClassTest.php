<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class DatabaseClassTest extends TestCase {
    public function testRsSelect() {
        $rsqueryMock = $this->getMockBuilder(RSQuery::class)
                            ->onlyMethods(['makeQuery'])
                            ->getMock();
        $res = new class {
            public function fetch_assoc() {
                return ["RS_TYPE" => 'identifier'];
            }
        };
        $rsqueryMock->method('makeQuery')->willReturn($res);
        $db = new Database();
        $db->attach($rsqueryMock);
        $result = $db->RSSelect('item_property', 'Type', [19, 1]);
        $this->assertEquals(["RS_TYPE" => 'identifier'], $result);
    }

    public function testRsUpdate() {
        $rsqueryMock = $this->getMockBuilder(RSQuery::class)
                            ->onlyMethods(['makeQuery'])
                            ->getMock();
        $rsqueryMock->method('makeQuery')->willReturn('something');
        $db = new Database();
        $db->attach($rsqueryMock);
        $result = $db->RSUpdate('item_types', 'MainProperty', [19, 1]);
        $this->assertEquals('something', $result);
    }

    public function testRsDelete() {
        $rsqueryMock = $this->getMockBuilder(RSQuery::class)
                            ->onlyMethods(['makeQuery'])
                            ->getMock();
        $rsqueryMock->method('makeQuery')->willReturn('something');
        $db = new Database();
        $db->attach($rsqueryMock);
        $result = $db->RSDelete('item_property', 'ClientProperty', [19, 1]);
        $this->assertEquals('something', $result);
    }

    public function testRsInsert() {
        $this->markTestIncomplete('This test has not been implemented yet.');
        $rsqueryMock = $this->getMockBuilder(RSQuery::class)
                            ->onlyMethods(['makeQuery'])
                            ->getMock();
        $rsqueryMock->method('makeQuery')->willReturn('something');
        $db = new Database();
        $db->attach($rsqueryMock);
        $result = $db->RSInsert('', '', [19, 1]); //Falta añadir alguna consulta de inserción
        $this->assertEquals('something', $result);
    }
}