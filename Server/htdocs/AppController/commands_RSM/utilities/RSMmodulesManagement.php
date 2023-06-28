<?php

//translate modules
function getModulesTranslated($userID, $clientID)
{
    // prepare query
    $theQuery = "SELECT rs_actions.RS_NAME, rs_actions.RS_CONFIGURATION_ITEMTYPE, rs_actions.RS_APPLICATION_NAME, rs_actions_clients.RS_CONFIGURATION_ITEM_ID FROM rs_actions INNER JOIN (rs_actions_clients INNER JOIN rs_actions_groups ON rs_actions_clients.RS_ID=rs_actions_groups.RS_ACTION_CLIENT_ID AND rs_actions_clients.RS_CLIENT_ID=rs_actions_groups.RS_CLIENT_ID) ON rs_actions.RS_ID=rs_actions_clients.RS_ACTION_ID WHERE rs_actions_groups.RS_CLIENT_ID=".$clientID." AND rs_actions_groups.RS_GROUP_ID IN ( SELECT rs_users_groups.RS_GROUP_ID FROM rs_users_groups INNER JOIN rs_groups ON rs_groups.RS_GROUP_ID=rs_users_groups.RS_GROUP_ID AND rs_groups.RS_CLIENT_ID=rs_users_groups.RS_CLIENT_ID WHERE rs_users_groups.RS_CLIENT_ID =".$clientID." AND rs_users_groups.RS_USER_ID =".$userID.") group by rs_actions_clients.RS_ID";

    // execute query and return
    return RSquery($theQuery);
}
