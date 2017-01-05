<?php

class CustomFilterShareApi extends SugarApi
{
    public function registerApiRest()
    {
        return array(
            'FilterShare' => array(
                'reqType' => 'GET',
                'path' => array('getUsersForFilterShare'),
                'pathVars' => array(''),
                'method' => 'getUsers',
                'shortHelp' => 'Retrieves the users grouped by roles and teams',
                'longHelp' => '',
            ),
            'FilterSave' => array(
                'reqType' => 'POST',
                'path' => array('shareFilterWithUsers'),
                'pathVars' => array(''),
                'method' => 'shareIt',
                'shortHelp' => 'Shares the given filter with the given users',
                'longHelp' => '',
            )
        );
    }

    function getUsers($api, $args)
    {
        $response = array();
        $roleUserSql = "SELECT
            u.first_name,
            u.last_name,
            u.user_name,
            u.id,
            ar.name as role_name
        FROM acl_roles_users acu
        INNER JOIN acl_roles ar ON acu.role_id = ar.id AND ar.deleted = 0
        INNER JOIN users u ON acu.user_id = u.id AND u.deleted = 0
        WHERE acu.deleted = 0
        ORDER BY ar.name";

        $roleUserQuery = $GLOBALS['db']->query($roleUserSql);
        $currentRole = "";
        $roleGroups = array();
        while ($row = $GLOBALS['db']->fetchByAssoc($roleUserQuery)) {
            if ($row['role_name'] != $currentRole && empty($roleGroups[$row['role_name']])) {
                $currentRole = $row['role_name'];
                $roleGroups[$currentRole] = array();
            }

            $roleGroups[$currentRole][] = array(
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name']
            );
        }

        $response['roles'] = $roleGroups;

        $teamUserSql = "SELECT
            u.first_name,
            u.last_name,
            u.user_name,
            u.id,
            t.name as team_name
        FROM team_memberships tm
        INNER JOIN teams t ON tm.team_id = t.id AND t.deleted = 0
        INNER JOIN users u ON tm.user_id = u.id AND u.deleted = 0
        WHERE tm.deleted = 0
        ORDER BY t.name";

        $teamUserQuery = $GLOBALS['db']->query($teamUserSql);
        $currentTeam = "";
        $teamGroups = array();
        while ($row = $GLOBALS['db']->fetchByAssoc($teamUserQuery)) {
            if ($row['team_name'] != $currentTeam && empty($roleGroups[$row['team_name']])) {
                $currentTeam = $row['team_name'];
                $teamGroups[$currentTeam] = array();
            }

            $teamGroups[$currentTeam][] = array(
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name']
            );
        }

        $response['teams'] = $teamGroups;

        $userSql = "SELECT
            u.first_name,
            u.last_name,
            u.user_name,
            u.id
        FROM users u
        WHERE u.deleted = 0
        ORDER BY u.first_name";

        $userQuery = $GLOBALS['db']->query($userSql);
        $users = array();
        while ($row = $GLOBALS['db']->fetchByAssoc($userQuery)) {
            $users[] = array(
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name']
            );
        }

        $response['users'] = $users;
        return $response;
    }

    public function shareIt($api, $args)
    {
        global $sugar_config;

        if (empty($args['user_ids']) || empty($args['filter_id'])) {
            throw new SugarApiExceptionMissingParameter();
        }

        $query = new SugarQuery();
        $query->from(BeanFactory::getBean("Filters"));
        $query->where()->equals('id',$args['filter_id']);

        $filter = $query->execute();

        if (!empty($filter)) {
            $newFilter = $filter[0];

            $userIds = $args['user_ids'];
            foreach($userIds as $user_id) {
                $query = new SugarQuery();
                $query->from(BeanFactory::getBean("Filters"), array('team_security' => false));
                $query->where()->equals('created_by',$user_id);
                $query->where()->equals('assigned_user_id',$user_id);
                $query->where()->equals('name',$newFilter['name']);
                $query->where()->equals('deleted', 0);

                //skip this filter if already shared
                if ($query->getOne() !== false) {
                    continue;
                }

                $newFilter['id'] = create_guid();
                $newFilter['created_by'] = $user_id;
                $newFilter['assigned_user_id'] = $user_id;
                $newFilter['modified_user_id'] = $user_id;

                $count = 0;
                $fields = '';


                foreach($newFilter as $col => $val) {
                    if ($count++ != 0) $fields .= ', ';
                    $fields .=  $col . " = " . $GLOBALS['db']->quoted($val);
                }

                $insertQuery = "INSERT INTO filters SET " . $fields . ";";

                if ($GLOBALS['db']->query($insertQuery)) {

                }
            }
        } else {
            throw new SugarApiExceptionInvalidParameter();
        }
    }
}
