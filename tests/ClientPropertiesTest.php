<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class ClientPropertiesTest extends TestCase {
    public function testGetAppPropertyDefaultValue() { // TODO: Check test name
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getAppPropertyID_RelatedWith', 'getAppPropertyDefaultValue', 'getPropertyDefaultValue'])
            ->getMock();
        $dbMock->method('getAppPropertyID_RelatedWith')->willReturn(34);
        $dbMock->method('getAppPropertyDefaultValue')->willReturn('New item');
        $dbMock->method('getPropertyDefaultValue')->willReturn('es-ES');
        $clientProperty = new ClientProperty($dbMock);
        $result = $clientProperty->getClientPropertyDefaultValue(19, 1);
        $this->assertEquals('New item', $result);
    }
    
    public function testGetClientPropertyDefaultValue() { // TODO: Check test name
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getAppPropertyID_RelatedWith', 'getAppPropertyDefaultValue', 'getPropertyDefaultValue'])
            ->getMock();
        $dbMock->method('getAppPropertyID_RelatedWith')->willReturn(34);
        $dbMock->method('getAppPropertyDefaultValue')->willReturn('');
        $dbMock->method('getPropertyDefaultValue')->willReturn('es-ES');
        $clientProperty = new ClientProperty($dbMock);
        $result = $clientProperty->getClientPropertyDefaultValue(19, 1);
        $this->assertEquals('es-ES', $result);
    }

    public function testGetClientPropertyReferredItemType() {
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getReferredItemType', 'getAppRelationsItemtype'])
            ->getMock();
        $dbMock->method('getReferredItemType')->willReturn(0);
        $dbMock->method('getAppRelationsItemtype')->willReturn(6);
        $clientProperty = new ClientProperty($dbMock);
        $result = $clientProperty->getClientPropertyReferredItemType(19, 1);
        $this->assertEquals('6', $result);
    }
    
    public function testGetClientPropertyReferredItemTypeByName() { //¿Tiene sentido testear esta función?
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getAppPropertyIDByName', 'getAppPropertyReferredItemType', 'getClientItemTypeID_RelatedWith'])
            ->getMock();
        $dbMock->method('getAppPropertyIDByName')->willReturn(5);
        $dbMock->method('getAppPropertyReferredItemType')->willReturn(3);
        $dbMock->method('getClientItemTypeID_RelatedWith')->willReturn(8);
        $clientProperty = new ClientProperty($dbMock);
        $result = $clientProperty->getClientPropertyReferredItemType_byName('projects.staff', 1);
        $this->assertEquals('8', $result);
    }
}
