INSERT INTO `rs_actions` (`RS_NAME`, `RS_DESCRIPTION`, `RS_APPLICATION_NAME`, `RS_APPLICATION_LOGO`, `RS_CONFIGURATION_ITEMTYPE`) VALUES
('rsm.mainpanel.HTML.access', 'Generic HTML module', 'Generic HTML module', '', 'configuration.module.HTMLModule');


INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(NULL, 'configuration.module.HTMLModule');


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'configuration.module.HTMLModule.name', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.HTMLModule.description', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.HTMLModule.logo', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'image', '0'), 
(NULL, 'configuration.module.HTMLModule.URL', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.HTMLModule.Method', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.HTMLModule.RequestVars', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule'), '', NULL , 'text', '0');


DELETE FROM `rs_actions_groups` 
WHERE (`RS_ACTION_CLIENT_ID`,`RS_CLIENT_ID`) IN (SELECT `RS_ID`,`RS_CLIENT_ID` FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.testautomation.access"));

DELETE FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.testautomation.access");

DELETE FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.testautomation.access";


DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'testcases.seleniumURL');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='testcases.seleniumURL';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'steps.script');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='steps.script';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'testing.execution.script');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='testing.execution.script';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'seleniumServerDefinition.hostIP');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='seleniumServerDefinition.hostIP';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'seleniumServerDefinition.port');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='seleniumServerDefinition.port';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'seleniumServerDefinition.browser');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='seleniumServerDefinition.browser';

DELETE FROM `rs_item_type_app_relations` 
WHERE `RS_ITEMTYPE_APP_ID`=(SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'seleniumServerDefinition');

DELETE FROM `rs_item_type_app_definitions` 
WHERE `RS_NAME`='seleniumServerDefinition';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.roundID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.roundID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.subjectID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.subjectID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.testCategoryParentID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.testCategoryParentID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.testCategoryID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.testCategoryID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.testCasesCount');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.testCasesCount';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.testCasesOKCount');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.testCasesOKCount';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'automatizationResultsRelations.testCasesNOKCount');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations.testCasesNOKCount';

DELETE FROM `rs_item_type_app_relations` 
WHERE `RS_ITEMTYPE_APP_ID`=(SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'automatizationResultsRelations');

DELETE FROM `rs_item_type_app_definitions` 
WHERE `RS_NAME`='automatizationResultsRelations';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'subject.seleniumDef');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='subject.seleniumDef';


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'revisionHistory.version', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'productBuild')),
(NULL, 'revisionHistory.affectedModules', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'text', '0'),
(NULL, 'revisionHistory.revision', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'text', '0'),
(NULL, 'revisionHistory.description.ES', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'longtext', '0'),
(NULL, 'revisionHistory.description.EN', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'longtext', '0'),
(NULL, 'revisionHistory.description.DE', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'revisionHistory'), '', NULL , 'longtext', '0'),
(NULL, 'productBuild.product', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'productBuild'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'studies'));