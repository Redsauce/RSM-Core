ALTER TABLE `rs_actions_clients` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `RS_CLIENT_ID` , `RS_ID` );


ALTER TABLE `rs_actions_groups` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `RS_CLIENT_ID` , `RS_GROUP_ID` , `RS_ACTION_CLIENT_ID` ) ;


ALTER TABLE `rs_actions_groups` DROP `RS_ACTION_ID`; 


ALTER TABLE `rs_actions_clients`
  DROP `RS_MODULE_NAME`,
  DROP `RS_MODULE_DESCRIPTION`,
  DROP `RS_MODULE_LOGO`;


INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
	VALUES 
	(NULL, '4.5.0.3.99', '4.6.0.3.103', NOW(), '');