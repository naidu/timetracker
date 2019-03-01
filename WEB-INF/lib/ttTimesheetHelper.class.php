<?php
// +----------------------------------------------------------------------+
// | Anuko Time Tracker
// +----------------------------------------------------------------------+
// | Copyright (c) Anuko International Ltd. (https://www.anuko.com)
// +----------------------------------------------------------------------+
// | LIBERAL FREEWARE LICENSE: This source code document may be used
// | by anyone for any purpose, and freely redistributed alone or in
// | combination with other software, provided that the license is obeyed.
// |
// | There are only two ways to violate the license:
// |
// | 1. To redistribute this code in source form, with the copyright
// |    notice or license removed or altered. (Distributing in compiled
// |    forms without embedded copyright notices is permitted).
// |
// | 2. To redistribute modified versions of this code in *any* form
// |    that bears insufficient indications that the modifications are
// |    not the work of the original author(s).
// |
// | This license applies to this document only, not any other software
// | that it may be combined with.
// |
// +----------------------------------------------------------------------+
// | Contributors:
// | https://www.anuko.com/time_tracker/credits.htm
// +----------------------------------------------------------------------+

import('ttUserHelper');
import('ttGroupHelper');
import('form.ActionForm');
import('ttReportHelper');

// Class ttTimesheetHelper is used to help with project related tasks.
class ttTimesheetHelper {

  // The getTimesheetByName looks up a project by name.
  static function getTimesheetByName($name, $user_id) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $sql = "select id from tt_timesheets".
      " where group_id = $group_id and org_id = $org_id and user_id = $user_id and name = ".$mdb2->quote($name).
      " and (status = 1 or status = 0)";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      $val = $res->fetchRow();
      if ($val && $val['id'])
        return $val;
    }
    return false;
  }

  // createTimesheet function creates a new timesheet.
  static function createTimesheet($fields)
  {
    // Create a new timesheet entry.
    global $user;
    $mdb2 = getConnection();

    $user_id = $user->getUser();
    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $client_id = $fields['client_id'];
    $name = $fields['name'];
    $comment = $fields['comment'];

    $start_date = new DateAndTime($user->date_format, $fields['start_date']);
    $start = $start_date->toString(DB_DATEFORMAT);

    $end_date = new DateAndTime($user->date_format, $fields['end_date']);
    $end = $end_date->toString(DB_DATEFORMAT);

    $sql = "insert into tt_timesheets (user_id, group_id, org_id, client_id, name, comment, start_date, end_date)".
      " values ($user_id, $group_id, $org_id, ".$mdb2->quote($client_id).", ".$mdb2->quote($name).
      ", ".$mdb2->quote($comment).", ".$mdb2->quote($start).", ".$mdb2->quote($end).")";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error'))
      return false;

    $last_id = $mdb2->lastInsertID('tt_timesheets', 'id');

    // Associate time items with timesheet.
    if (isset($fields['client'])) $client_id = (int) $fields['client_id'];
    if (isset($fields['project_id'])) $project_id = (int) $fields['project_id'];
    // sql parts.
    if ($client_id) $client_part = " and client_id = $client_id";
    if ($project_id) $project_part = " and project_id = $project_id";

    $sql = "update tt_log set timesheet_id = $last_id".
      " where status = 1 $client_part $project_part and timesheet_id is null".
      " and date >= ".$mdb2->quote($start)." and date <= ".$mdb2->quote($end).
      " and user_id = $user_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error'))
      return false;

    return $last_id;
  }

  // The getActiveTimesheets obtains active timesheets for a user.
  static function getActiveTimesheets($user_id)
  {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // $addPaidStatus = $user->isPluginEnabled('ps');
    $result = array();

    if ($user->isClient())
      $client_part = "and ts.client_id = $user->client_id";

    $sql = "select ts.id, ts.name, ts.client_id, c.name as client_name, ts.submit_status, ts.approve_status from tt_timesheets ts".
      " left join tt_clients c on (c.id = ts.client_id)".
      " where ts.status = 1 and ts.group_id = $group_id and ts.org_id = $org_id and ts.user_id = $user_id".
      " $client_part order by ts.name";
    $res = $mdb2->query($sql);
    $result = array();
    if (!is_a($res, 'PEAR_Error')) {
      $dt = new DateAndTime(DB_DATEFORMAT);
      while ($val = $res->fetchRow()) {
        //if ($addPaidStatus)
        //  $val['paid'] = ttTimesheetHelper::isPaid($val['id']);
        $result[] = $val;
      }
    }
    return $result;
  }

  // The getInactiveTimesheets obtains inactive timesheets for a user.
  static function getInactiveTimesheets($user_id)
  {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // $addPaidStatus = $user->isPluginEnabled('ps');
    $result = array();

    if ($user->isClient())
      $client_part = "and ts.client_id = $user->client_id";

    $sql = "select ts.id, ts.name, ts.client_id, c.name as client_name, ts.submit_status, ts.approval_status from tt_timesheets ts".
      " left join tt_clients c on (c.id = ts.client_id)".
      " where ts.status = 0 and ts.group_id = $group_id and ts.org_id = $org_id and ts.user_id = $user_id".
      " $client_part order by ts.name";
    $res = $mdb2->query($sql);
    $result = array();
    if (!is_a($res, 'PEAR_Error')) {
      $dt = new DateAndTime(DB_DATEFORMAT);
      while ($val = $res->fetchRow()) {
        //if ($addPaidStatus)
        //  $val['paid'] = ttTimesheetHelper::isPaid($val['id']);
        $result[] = $val;
      }
    }
    return $result;
  }

  // getTimesheet - obtains timesheet data from the database.
  static function getTimesheet($timesheet_id) {
    global $user;
    $mdb2 = getConnection();

    $user_id = $user->getUser();
    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $sql = "select * from tt_timesheets".
      " where id = $timesheet_id and user_id = $user_id and group_id = $group_id and org_id = $org_id and status is not null";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      if ($val = $res->fetchRow())
        return $val;
    }
    return false;
  }

  // delete - deletes timesheet from the database.
  static function delete($timesheet_id) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // Handle time records.
    $sql = "update tt_log set timesheet_id = null".
      " where timesheet_id = $timesheet_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error')) return false;

    // Handle expense items.
    $sql = "update tt_expense_items set timesheet_id = null".
      " where timesheet_id = $timesheet_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error')) return false;

    // Delete timesheet.
    $sql = "update tt_timesheets set status = null".
      " where id = $timesheet_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    return (!is_a($affected, 'PEAR_Error'));
  }

  // update function - updates the timesheet in database.
  static function update($fields) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $timesheet_id = $fields['id']; // Timesheet we are updating.
    $name = $fields['name']; // Timesheet name.
    $submitter_comment = $fields['submitter_comment'];
    $status = $fields['status']; // Project status.

    $sql = "update tt_timesheets set name = ".$mdb2->quote($name).", submitter_comment = ".$mdb2->quote($submitter_comment).
      ", status = ".$mdb2->quote($status).
      " where id = $timesheet_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    return (!is_a($affected, 'PEAR_Error'));
  }

  // isUserValid function is used during access checks and determines whether user id, passed in post, is valid
  // in current context.
  static function isUserValid($user_id) {
    // We have to cover several situations.
    //
    // 1) User is a client.
    // 2) User with view_all_timesheets rights.
    // 3) User with view_timesheets rights.

    global $user;

    // TODO: we are currently re-designing timesheets.
    // Clients are not supposed to view them at all.
    // And the post will change on_behalf user, to keep things consistent.
    return false;
  }

  // getReportOptions prepares $options array to be used with ttReportHelper
  // to obtain items for timesheet view.
  static function getReportOptions($timesheet) {
    global $user;
    $group_by_client = $user->isPluginEnabled('cl') && !$timesheet['client_id'];
    $trackingMode = $user->getTrackingMode();
    $group_by_project = MODE_PROJECTS == $trackingMode || MODE_PROJECTS_AND_TASKS == $trackingMode;

    $options['timesheet_id'] = $timesheet['id'];
    $options['client_id'] = $timesheet['client_id'];
    $options['users'] = $timesheet['user_id'];
    $options['show_durarion'] = 1;
    $options['show_cost'] = 1; // To include expenses.
    $options['show_totals_only'] = 1;
    $options['group_by1'] = 'date';
    if ($group_by_client || $group_by_project) {
      $options['group_by2'] = $group_by_client ? 'client' : 'project';
    }
    if ($options['group_by2'] && $options['group_by2'] != 'project' && $group_by_project) {
      $options['group_by3'] = 'project';
    }
    return $options;
  }

  // getApprovers obtains a list of users who can approve a timesheet for a given user
  // and also have an email to receive a notification about it.
  static function getApprovers($user_id) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    $approvers = array();
    $rank = ttUserHelper::getUserRank($user_id);
    $sql = "select u.id, u.name, u.email".
      " from tt_users u".
      " left join tt_roles r on (r.id = u.role_id)".
      " where u.status = 1 and u.email is not null and u.group_id = $group_id and u.org_id = $org_id".
      " and (r.rights like '%approve_all_timesheets%' or (r.rank > $rank and r.rights like '%approve_timesheets%'))";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      while ($val = $res->fetchRow()) {
        $approvers[] = $val;
      }
    }
    return $approvers;
  }

  // submitTimesheet marks a timesheet as submitted and sends an email to an approver.
  static function submitTimesheet($fields) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // First, mark a timesheet as submitted.
    // Even if mail part below does not work, this will get us a functioning workflow
    // (without email notifications).
    $timesheet_id = $fields['timesheet_id'];
    $sql = "update tt_timesheets set submit_status = 1".
      " where id = $timesheet_id and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error')) return false;

    // TODO: send email to approver here...
    // $approver_id = $fields['approver_id'];

    return true;
  }

  // approveTimesheet marks a timesheet as approved and sends an email to submitter.
  static function approveTimesheet($fields) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // First, mark a timesheet as approved.
    // Even if mail part below does not work, this will get us a functioning workflow
    // (without email notifications).
    $timesheet_id = $fields['timesheet_id'];
    $manager_comment = $fields['comment'];

    $sql = "update tt_timesheets set approval_status = 1, manager_comment = ".$mdb2->quote($manager_comment).
      " where id = $timesheet_id and submit_status = 1 and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error')) return false;

    // TODO: send email to submitter here...
    return true;
  }

  // disapproveTimesheet marks a timesheet as approved and sends an email to submitter.
  static function disapproveTimesheet($fields) {
    global $user;
    $mdb2 = getConnection();

    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    // First, mark a timesheet as disapproved.
    // Even if mail part below does not work, this will get us a functioning workflow
    // (without email notifications).
    $timesheet_id = $fields['timesheet_id'];
    $manager_comment = $fields['comment'];

    $sql = "update tt_timesheets set approval_status = 0, manager_comment = ".$mdb2->quote($manager_comment).
      " where id = $timesheet_id and submit_status = 1 and group_id = $group_id and org_id = $org_id";
    $affected = $mdb2->exec($sql);
    if (is_a($affected, 'PEAR_Error')) return false;

    // TODO: send email to submitter here...
    return true;
  }

  // The timesheetItemsExist determines whether tt_log records exist in the specified period
  // for inclusion in a new timesheet.
  static function timesheetItemsExist($fields) {
    global $user;
    $mdb2 = getConnection();

    $user_id = $user->getUser();
    $group_id = $user->getGroup();
    $org_id = $user->org_id;

    if (isset($fields['client_id'])) $client_id = (int) $fields['client_id'];
    if (isset($fields['project_id'])) $project_id = (int) $fields['project_id'];

    $start_date = new DateAndTime($user->date_format, $fields['start_date']);
    $start = $start_date->toString(DB_DATEFORMAT);

    $end_date = new DateAndTime($user->date_format, $fields['end_date']);
    $end = $end_date->toString(DB_DATEFORMAT);

    // sql parts.
    if ($client_id) $client_part = " and client_id = $client_id";
    if ($project_id) $project_part = " and project_id = $project_id";

    $sql = "select count(*) as num from tt_log".
      " where status = 1 $client_part $project_part and timesheet_id is null".
      " and date >= ".$mdb2->quote($start)." and date <= ".$mdb2->quote($end).
      " and user_id = $user_id and group_id = $group_id and org_id = $org_id";
    $res = $mdb2->query($sql);
    if (!is_a($res, 'PEAR_Error')) {
      $val = $res->fetchRow();
      if ($val['num']) {
        return true;
      }
    }

    return false;
  }
}
